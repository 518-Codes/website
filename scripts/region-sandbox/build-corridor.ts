import { mkdir } from 'node:fs/promises';
import { PNG } from 'pngjs';
import {
  SEGMENTS, PROJECTION, PATH_WAYPOINTS, REGION_BOUNDARY_LAT,
  CORRIDOR_CITIES, OUTPUT_DIR, type BBox,
} from './config';
import { chunkBBoxesForSegment, regionOf, chunkGridHeight } from './segments';
import { bboxTileRange } from './tiles';
import { decodeElevation, downsample } from './terrarium';
import { bboxPixelRect, cropGrid } from './crop';
import { projectToScene } from './project';
import { simplify, type Pt } from './simplify';
import { parseElements, type RawFeature, type FeatureKind } from './overpass';

const TILE = 256;
const DEM_ZOOM = 10;
const GRID_W = 256;
const CORRIDOR_DIR = `${OUTPUT_DIR}/corridor`;

type Feature = { kind: FeatureKind; coords: Pt[]; closed?: boolean };
type ChunkPlan = {
  globalIndex: number;
  segment: string;
  region: string;
  bbox: BBox;
  gridH: number;
  light: boolean;
};

async function fetchTilePng(z: number, x: number, y: number): Promise<PNG> {
  const url = `https://s3.amazonaws.com/elevation-tiles-prod/terrarium/${z}/${x}/${y}.png`;
  const res = await fetch(url);
  if (!res.ok) { throw new Error(`DEM tile HTTP ${res.status} for ${z}/${x}/${y}`); }
  return PNG.sync.read(Buffer.from(await res.arrayBuffer()));
}

/**
 * Fetch + downsample a chunk's DEM to a RAW (meters) Float32 grid (GRID_W × gridH).
 * The tile mosaic is snapped outside the bbox, so it is cropped to the exact bbox
 * before downsampling — otherwise the heightmap is offset from the bbox (and from the
 * features, which use the precise bbox) by tens of cells.
 */
async function fetchRawHeightmap(bbox: BBox, gridH: number): Promise<Float32Array> {
  const r = bboxTileRange(bbox, DEM_ZOOM);
  const cols = r.maxX - r.minX + 1;
  const rows = r.maxY - r.minY + 1;
  const mosaicW = cols * TILE;
  const mosaicH = rows * TILE;
  const mosaic = new Float32Array(mosaicW * mosaicH);

  for (let ty = r.minY; ty <= r.maxY; ty++) {
    for (let tx = r.minX; tx <= r.maxX; tx++) {
      const png = await fetchTilePng(DEM_ZOOM, tx, ty);
      const ox = (tx - r.minX) * TILE;
      const oy = (ty - r.minY) * TILE;
      for (let py = 0; py < TILE; py++) {
        for (let px = 0; px < TILE; px++) {
          const i = (py * TILE + px) * 4;
          const elev = decodeElevation(png.data[i], png.data[i + 1], png.data[i + 2]);
          mosaic[(oy + py) * mosaicW + (ox + px)] = elev;
        }
      }
    }
  }

  const crop = cropGrid(mosaic, mosaicW, mosaicH, bboxPixelRect(bbox, r, DEM_ZOOM, TILE));
  return downsample(crop.data, crop.w, crop.h, GRID_W, gridH);
}

/** Linear normalize a raw grid to [0,1] against supplied min/max, rounded to 3 decimals. */
function normalizeAgainst(grid: Float32Array, min: number, max: number): number[] {
  const range = max - min || 1;
  return Array.from(grid, (v) => Math.round(((v - min) / range) * 1000) / 1000);
}

/**
 * Overpass fetch mirroring the shared query (major/trunk/primary/secondary roads,
 * river/canal/stream, natural=water). Dense/low areas use the lighter query so Overpass
 * doesn't 504 and files stay lean.
 */
async function fetchOverpassChunk(bbox: BBox, timeoutMs: number, light: boolean): Promise<RawFeature[]> {
  const bb = `${bbox.minLat},${bbox.minLng},${bbox.maxLat},${bbox.maxLng}`;
  const roads = light ? 'motorway|trunk|primary' : 'motorway|trunk|primary|secondary';
  const water = light ? 'river|canal' : 'river|canal|stream';
  const query = `[out:json][timeout:180];
(
  way["highway"~"${roads}"](${bb});
  way["waterway"~"${water}"](${bb});
  way["natural"="water"](${bb});
);
out geom;`;
  const ctrl = new AbortController();
  const timer = setTimeout(() => ctrl.abort(), timeoutMs);
  try {
    const res = await fetch('https://overpass-api.de/api/interpreter', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Accept': 'application/json',
        'User-Agent': '518codes-region-sandbox/1.0',
      },
      body: 'data=' + encodeURIComponent(query),
      signal: ctrl.signal,
    });
    if (!res.ok) { throw new Error(`Overpass HTTP ${res.status}`); }
    return parseElements((await res.json()) as { elements?: any[] });
  } finally {
    clearTimeout(timer);
  }
}

/** Fetch a chunk's features with retries; returns [] + flags failure on repeated error. */
async function buildFeatures(bbox: BBox, light: boolean): Promise<{ features: Feature[]; failed: boolean }> {
  for (let attempt = 1; attempt <= 3; attempt++) {
    try {
      const raw = await fetchOverpassChunk(bbox, 240_000, light);
      const features = raw
        .map((f) => {
          const pts: Pt[] = f.coords.map(([lng, lat]) => {
            const p = projectToScene(lng, lat, bbox);
            return [Math.round(p.x * 1e4) / 1e4, Math.round(p.z * 1e4) / 1e4];
          });
          const epsilon = f.kind === 'water-area' ? 0.0002 : 0.002;
          const coords = simplify(pts, epsilon);
          const feature: Feature = { kind: f.kind, coords };
          if (f.closed) { feature.closed = true; }
          return feature;
        })
        .filter((f) => f.coords.length >= 2);
      return { features, failed: false };
    } catch (e) {
      console.warn(`  ! Overpass attempt ${attempt} failed: ${(e as Error).message}`);
      if (attempt < 3) { await new Promise((r) => setTimeout(r, attempt * 6000)); } // backoff for 429/504
    }
  }
  console.warn('  ! Overpass failed 3x — baking chunk with empty features.');
  return { features: [], failed: true };
}

/** Cities inside a chunk bbox, projected to chunk-local coords. */
function citiesForChunk(bbox: BBox): { name: string; x: number; z: number }[] {
  return CORRIDOR_CITIES
    .filter((c) => c.lat >= bbox.minLat && c.lat < bbox.maxLat && c.lng >= bbox.minLng && c.lng < bbox.maxLng)
    .map((c) => {
      const p = projectToScene(c.lng, c.lat, bbox);
      return { name: c.name, x: Math.round(p.x * 1e4) / 1e4, z: Math.round(p.z * 1e4) / 1e4 };
    });
}

function countByKind(features: Feature[]): Record<string, number> {
  const counts: Record<string, number> = {};
  for (const f of features) { counts[f.kind] = (counts[f.kind] ?? 0) + 1; }
  return counts;
}

async function main(): Promise<void> {
  await mkdir(CORRIDOR_DIR, { recursive: true });

  // Ordered chunk plan across all segments (mainline first, then long island).
  const plans: ChunkPlan[] = [];
  let gi = 0;
  for (const seg of SEGMENTS) {
    for (const bbox of chunkBBoxesForSegment(seg)) {
      const region = regionOf(seg.id, bbox, REGION_BOUNDARY_LAT);
      const light = seg.id === 'longisland' || bbox.maxLat < 42.5; // dense/low areas use the lighter query
      plans.push({ globalIndex: gi++, segment: seg.id, region, bbox, gridH: chunkGridHeight(bbox, GRID_W), light });
    }
  }
  console.log(`Planned ${plans.length} chunks across ${SEGMENTS.length} segments.`);

  // Phase 1: raw heightmaps for every chunk + per-region min/max.
  console.log('\nFetching DEM for all chunks…');
  const rawGrids: Float32Array[] = [];
  const regionExtent = new Map<string, { min: number; max: number }>();
  for (const plan of plans) {
    process.stdout.write(`  chunk ${plan.globalIndex} [${plan.region}] DEM (lat ${plan.bbox.minLat.toFixed(2)}..${plan.bbox.maxLat.toFixed(2)}, lng ${plan.bbox.minLng.toFixed(2)}..${plan.bbox.maxLng.toFixed(2)})… `);
    const grid = await fetchRawHeightmap(plan.bbox, plan.gridH);
    rawGrids.push(grid);
    let e = regionExtent.get(plan.region);
    if (!e) { e = { min: Infinity, max: -Infinity }; regionExtent.set(plan.region, e); }
    for (const v of grid) { if (v < e.min) { e.min = v; } if (v > e.max) { e.max = v; } }
    console.log('done');
  }
  for (const [id, e] of regionExtent) { console.log(`Region ${id}: ${e.min.toFixed(1)}m .. ${e.max.toFixed(1)}m`); }

  // Phase 2: features + cities per chunk, write chunk files (normalized per region).
  const manifestChunks: {
    index: number; file: string; segment: string; region: string;
    bbox: BBox; grid: { width: number; height: number };
    cities: { name: string; x: number; z: number }[];
  }[] = [];
  const failedChunks: number[] = [];

  for (const plan of plans) {
    const i = plan.globalIndex;
    console.log(`\nChunk ${i} features [${plan.segment}/${plan.region}]${plan.light ? ' [light]' : ' [full]'}…`);
    if (i > 0) { await new Promise((r) => setTimeout(r, 3000)); } // gentle on Overpass between chunks
    const { features, failed } = await buildFeatures(plan.bbox, plan.light);
    if (failed) { failedChunks.push(i); }
    const cities = citiesForChunk(plan.bbox);
    const ext = regionExtent.get(plan.region)!;
    const data = normalizeAgainst(rawGrids[i], ext.min, ext.max);
    const grid = { width: GRID_W, height: plan.gridH };

    const chunk = {
      index: i, segment: plan.segment, region: plan.region, bbox: plan.bbox,
      grid, heightmap: { width: GRID_W, height: plan.gridH, data }, features, cities,
    };
    const file = `chunk-${i}.json`;
    await Bun.write(`${CORRIDOR_DIR}/${file}`, JSON.stringify(chunk));
    const kb = ((await Bun.file(`${CORRIDOR_DIR}/${file}`).stat()).size / 1024).toFixed(1);
    console.log(`  ${features.length} features ${JSON.stringify(countByKind(features))}, ${cities.length} cities [${cities.map((c) => c.name).join(', ')}], ${kb} KB`);

    manifestChunks.push({ index: i, file, segment: plan.segment, region: plan.region, bbox: plan.bbox, grid, cities });
  }

  const manifest = {
    version: 2,
    projection: PROJECTION,
    regions: [...regionExtent].map(([id, e]) => ({ id, elevMin: e.min, elevMax: e.max })),
    path: { waypoints: PATH_WAYPOINTS },
    chunks: manifestChunks,
  };
  await Bun.write(`${CORRIDOR_DIR}/manifest.json`, JSON.stringify(manifest, null, 2));

  console.log(`\nWrote ${plans.length} chunks + manifest v2 to ${CORRIDOR_DIR}/`);
  if (failedChunks.length) {
    console.warn(`WARNING: chunks with empty features (Overpass failed): ${failedChunks.join(', ')}`);
  } else {
    console.log('All chunks fetched features successfully.');
  }
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
