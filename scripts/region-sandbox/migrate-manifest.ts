// scripts/region-sandbox/migrate-manifest.ts
// One-shot: upgrade the existing v1 corridor manifest + chunks to v2 (adds
// projection, path, per-chunk bbox/grid/region). No network; pure file rewrite.
import { OUTPUT_DIR } from './config';

const DIR = `${OUTPUT_DIR}/corridor`;
const LNG_REF = -73.85;
const LAT_REF = 45.0;
const UNITS_PER_DEG = 2 / 0.9;

const v1 = await Bun.file(`${DIR}/manifest.json`).json();
const c = v1.corridor; // { minLng, minLat, maxLng, maxLat }

const chunks = [];
for (const ch of v1.chunks) {
  const bbox = { minLng: c.minLng, minLat: ch.minLat, maxLng: c.maxLng, maxLat: ch.maxLat };
  const file = ch.file;
  const chunkData = await Bun.file(`${DIR}/${file}`).json();
  const region = 'hudson';
  const grid = { width: chunkData.heightmap.width, height: chunkData.heightmap.height };

  // Rewrite the chunk file with the new metadata (heightmap/features/cities untouched).
  chunkData.segment = 'mainline';
  chunkData.region = region;
  chunkData.bbox = bbox;
  chunkData.grid = grid;
  await Bun.write(`${DIR}/${file}`, JSON.stringify(chunkData));

  chunks.push({
    index: ch.index, file, segment: 'mainline', region, bbox, grid,
    cities: chunkData.cities ?? [],
  });
}

const manifest = {
  version: 2,
  projection: { lngRef: LNG_REF, latRef: LAT_REF, unitsPerDeg: UNITS_PER_DEG },
  regions: [{ id: 'hudson' }],
  path: {
    waypoints: [
      { lng: LNG_REF, lat: c.maxLat }, // north end of the existing corridor
      { lng: LNG_REF, lat: c.minLat }, // south end (NYC)
    ],
  },
  chunks,
};
await Bun.write(`${DIR}/manifest.json`, JSON.stringify(manifest, null, 2));
console.log(`Migrated ${chunks.length} chunks to manifest v2.`);
