import { mkdir } from 'node:fs/promises';
import { PNG } from 'pngjs'; // bun add -d pngjs (added in this task's step 2)
import { BBOX, CITIES, DEM_ZOOM, GRID_W, GRID_H, OUTPUT_DIR } from './config';
import { bboxTileRange } from './tiles';
import { decodeElevation, downsample, normalize } from './terrarium';
import { projectToScene } from './project';
import { simplify, type Pt } from './simplify';
import { fetchOverpass } from './overpass';

const TILE = 256;

async function fetchTilePng(z: number, x: number, y: number): Promise<PNG> {
  const url = `https://s3.amazonaws.com/elevation-tiles-prod/terrarium/${z}/${x}/${y}.png`;
  const res = await fetch(url);
  if (!res.ok) throw new Error(`DEM tile HTTP ${res.status} for ${z}/${x}/${y}`);
  return PNG.sync.read(Buffer.from(await res.arrayBuffer()));
}

async function buildHeightmap(): Promise<number[]> {
  const r = bboxTileRange(BBOX, DEM_ZOOM);
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
  const grid = normalize(downsample(mosaic, mosaicW, mosaicH, GRID_W, GRID_H));
  return Array.from(grid).map((v) => Math.round(v * 1000) / 1000);
}

async function buildFeatures() {
  const raw = await fetchOverpass(BBOX);
  return raw.map((f) => {
    const pts: Pt[] = f.coords.map(([lng, lat]) => {
      const p = projectToScene(lng, lat, BBOX);
      return [Math.round(p.x * 1e4) / 1e4, Math.round(p.z * 1e4) / 1e4];
    });
    const epsilon = f.kind === 'water-area' ? 0.0008 : 0.002;
    const coords = simplify(pts, epsilon);
    const feature: { kind: typeof f.kind; coords: Pt[]; closed?: boolean } = { kind: f.kind, coords };
    if (f.closed) { feature.closed = true; }
    return feature;
  }).filter((f) => f.coords.length >= 2);
}

function buildCities() {
  return CITIES.map((c) => {
    const p = projectToScene(c.lng, c.lat, BBOX);
    return { name: c.name, x: Math.round(p.x * 1e4) / 1e4, z: Math.round(p.z * 1e4) / 1e4 };
  });
}

async function main() {
  await mkdir(OUTPUT_DIR, { recursive: true });
  console.log('Building heightmap…');
  const heightmap = { width: GRID_W, height: GRID_H, data: await buildHeightmap() };
  console.log('Fetching features…');
  const features = await buildFeatures();
  const cities = buildCities();

  await Bun.write(`${OUTPUT_DIR}/heightmap.json`, JSON.stringify(heightmap));
  await Bun.write(`${OUTPUT_DIR}/features.json`, JSON.stringify(features));
  await Bun.write(`${OUTPUT_DIR}/cities.json`, JSON.stringify(cities));
  console.log(`Wrote ${features.length} features, ${cities.length} cities, ${GRID_W}x${GRID_H} heightmap.`);
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
