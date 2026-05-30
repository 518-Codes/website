import type { BBox } from './config';

export type FeatureKind = 'road-major' | 'road-sub' | 'water-river' | 'water-stream' | 'water-area';
export type RawFeature = { kind: FeatureKind; coords: [number, number][]; closed?: boolean };

/** Overpass QL for major/sub roads + waterways/water areas in a bbox. */
export function buildQuery(bbox: BBox): string {
  const bb = `${bbox.minLat},${bbox.minLng},${bbox.maxLat},${bbox.maxLng}`; // s,w,n,e
  return `[out:json][timeout:60];
(
  way["highway"~"motorway|trunk|primary|secondary"](${bb});
  way["waterway"~"river|canal|stream"](${bb});
  way["natural"="water"](${bb});
);
out geom;`;
}

const ROAD_MAJOR = new Set(['motorway', 'trunk', 'motorway_link', 'trunk_link']);
const ROAD_SUB = new Set(['primary', 'secondary', 'primary_link', 'secondary_link']);

/** Classify + extract way geometries; ignore non-way / untagged elements. */
export function parseElements(json: { elements?: any[] }): RawFeature[] {
  const out: RawFeature[] = [];
  for (const el of json.elements ?? []) {
    if (el.type !== 'way' || !Array.isArray(el.geometry) || el.geometry.length < 2) { continue; }
    const tags = el.tags ?? {};
    const coords: [number, number][] = el.geometry.map((g: any) => [g.lon, g.lat]);

    if (tags.highway) {
      if (ROAD_MAJOR.has(tags.highway)) {
        out.push({ kind: 'road-major', coords });
      } else if (ROAD_SUB.has(tags.highway)) {
        out.push({ kind: 'road-sub', coords });
      }
      // other highway values are skipped
    } else if (tags.natural === 'water') {
      out.push({ kind: 'water-area', coords, closed: true });
    } else if (tags.waterway) {
      const wk = (tags.waterway === 'river' || tags.waterway === 'canal') ? 'water-river' : 'water-stream';
      out.push({ kind: wk, coords });
    }
  }
  return out;
}

/** Thin network wrapper (not unit-tested). */
export async function fetchOverpass(bbox: BBox): Promise<RawFeature[]> {
  const res = await fetch('https://overpass-api.de/api/interpreter', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'Accept': 'application/json',
      'User-Agent': '518codes-region-sandbox/1.0',
    },
    body: 'data=' + encodeURIComponent(buildQuery(bbox)),
  });
  if (!res.ok) throw new Error(`Overpass HTTP ${res.status}`);
  return parseElements((await res.json()) as { elements?: any[] });
}
