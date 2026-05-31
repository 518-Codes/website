import { mkdir } from 'node:fs/promises';
import { PNG } from 'pngjs';
import { CORRIDOR, CHUNK_COUNT, CHUNK_GRID_W, CORRIDOR_CITIES, OUTPUT_DIR, type BBox } from './config';
import { bboxTileRange } from './tiles';
import { decodeElevation, downsample } from './terrarium';
import { projectToScene } from './project';
import { simplify, type Pt } from './simplify';
import { parseElements, type RawFeature, type FeatureKind } from './overpass';

const TILE = 256;
const DEM_ZOOM = 10;
const CORRIDOR_DIR = `${OUTPUT_DIR}/corridor`;

const latSpan = (CORRIDOR.maxLat - CORRIDOR.minLat) / CHUNK_COUNT;
const lngSpan = CORRIDOR.maxLng - CORRIDOR.minLng;
const CHUNK_GRID_H = Math.round(CHUNK_GRID_W * (latSpan / lngSpan));

type Feature = { kind: FeatureKind; coords: Pt[]; closed?: boolean };

function chunkBBox(i: number): BBox {
  const maxLat = CORRIDOR.maxLat - i * latSpan;
  const minLat = maxLat - latSpan;
  return { minLng: CORRIDOR.minLng, minLat, maxLng: CORRIDOR.maxLng, maxLat };
}

async function fetchTilePng(z: number, x: number, y: number): Promise<PNG> {
  const url = `https://s3.amazonaws.com/elevation-tiles-prod/terrarium/${z}/${x}/${y}.png`;
  const res = await fetch(url);
  if (!res.ok) { throw new Error(`DEM tile HTTP ${res.status} for ${z}/${x}/${y}`); }
  return PNG.sync.read(Buffer.from(await res.arrayBuffer()));
}

/** Fetch + downsample a chunk's DEM to a RAW (meters) Float32 grid. */
async function fetchRawHeightmap(bbox: BBox): Promise<Float32Array> {
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
  return downsample(mosaic, mosaicW, mosaicH, CHUNK_GRID_W, CHUNK_GRID_H);
}

/** Linear normalize a grid to [0,1] against a supplied global min/max, rounded to 3 decimals. */
function normalizeGlobal(grid: Float32Array, min: number, max: number): number[] {
  const range = max - min || 1;
  return Array.from(grid, (v) => Math.round(((v - min) / range) * 1000) / 1000);
}

/**
 * Overpass fetch with a generous server-side + client-side timeout. Mirrors the
 * shared query (major/trunk/primary/secondary, river/canal/stream, natural=water)
 * but allows a longer timeout than the shared helper for the dense metro chunks.
 */
async function fetchOverpassChunk(bbox: BBox, timeoutMs: number, light: boolean): Promise<RawFeature[]> {
  const bb = `${bbox.minLat},${bbox.minLng},${bbox.maxLat},${bbox.maxLng}`;
  // Dense southern (NYC-metro) chunks use a lighter query (drop secondary roads +
  // streams) so Overpass doesn't 504 and files stay lean; the north keeps full detail.
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

/** Fetch a chunk's features with one retry; returns [] + flags failure on repeated error. */
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

/** Cities whose lat falls within [minLat, maxLat), projected to chunk-local coords. */
function citiesForChunk(bbox: BBox): { name: string; x: number; z: number }[] {
  return CORRIDOR_CITIES
    .filter((c) => c.lat >= bbox.minLat && c.lat < bbox.maxLat)
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
  console.log(`Corridor: latSpan=${latSpan.toFixed(4)} lngSpan=${lngSpan.toFixed(4)} chunkGridH=${CHUNK_GRID_H} chunkAspect=${(CHUNK_GRID_H / CHUNK_GRID_W).toFixed(4)}`);

  // Phase 1: raw heightmaps for every chunk + global min/max.
  console.log('\nFetching DEM for all chunks…');
  const rawGrids: Float32Array[] = [];
  let globalMin = Infinity;
  let globalMax = -Infinity;
  for (let i = 0; i < CHUNK_COUNT; i++) {
    const bbox = chunkBBox(i);
    process.stdout.write(`  chunk ${i} DEM (lat ${bbox.minLat.toFixed(3)}..${bbox.maxLat.toFixed(3)})… `);
    const grid = await fetchRawHeightmap(bbox);
    for (const v of grid) {
      if (v < globalMin) { globalMin = v; }
      if (v > globalMax) { globalMax = v; }
    }
    rawGrids.push(grid);
    console.log('done');
  }
  console.log(`Global elevation range: ${globalMin.toFixed(1)}m .. ${globalMax.toFixed(1)}m`);

  // Phase 2: per-chunk features + cities, write chunk files.
  const manifestChunks: {
    index: number;
    file: string;
    minLat: number;
    maxLat: number;
    cityNames: string[];
  }[] = [];
  const failedChunks: number[] = [];

  for (let i = 0; i < CHUNK_COUNT; i++) {
    const bbox = chunkBBox(i);
    const light = bbox.maxLat < 42.5; // dense south (Hudson Valley → NYC) uses the lighter query
    console.log(`\nChunk ${i} features (lat ${bbox.minLat.toFixed(3)}..${bbox.maxLat.toFixed(3)})${light ? ' [light]' : ' [full]'}…`);
    if (i > 0) { await new Promise((r) => setTimeout(r, 3000)); } // gentle on Overpass between chunks (avoid 429)
    const { features, failed } = await buildFeatures(bbox, light);
    if (failed) { failedChunks.push(i); }
    const cities = citiesForChunk(bbox);
    const data = normalizeGlobal(rawGrids[i], globalMin, globalMax);

    const chunk = {
      index: i,
      bbox,
      heightmap: { width: CHUNK_GRID_W, height: CHUNK_GRID_H, data },
      features,
      cities,
    };
    const file = `chunk-${i}.json`;
    await Bun.write(`${CORRIDOR_DIR}/${file}`, JSON.stringify(chunk));

    const stat = await Bun.file(`${CORRIDOR_DIR}/${file}`).stat();
    const kb = (stat.size / 1024).toFixed(1);
    console.log(`  ${features.length} features ${JSON.stringify(countByKind(features))}, ${cities.length} cities [${cities.map((c) => c.name).join(', ')}], ${kb} KB`);

    manifestChunks.push({
      index: i,
      file,
      minLat: bbox.minLat,
      maxLat: bbox.maxLat,
      cityNames: cities.map((c) => c.name),
    });
  }

  const manifest = {
    corridor: {
      minLng: CORRIDOR.minLng,
      minLat: CORRIDOR.minLat,
      maxLng: CORRIDOR.maxLng,
      maxLat: CORRIDOR.maxLat,
    },
    chunkCount: CHUNK_COUNT,
    gridW: CHUNK_GRID_W,
    chunkGridH: CHUNK_GRID_H,
    latSpan,
    lngSpan,
    chunkAspect: CHUNK_GRID_H / CHUNK_GRID_W,
    chunks: manifestChunks,
  };
  await Bun.write(`${CORRIDOR_DIR}/manifest.json`, JSON.stringify(manifest, null, 2));

  console.log(`\nWrote ${CHUNK_COUNT} chunks + manifest to ${CORRIDOR_DIR}/`);
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
