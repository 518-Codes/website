import type { BBox } from './config';

export type FeatureKind = 'road' | 'water';
export type RawFeature = { kind: FeatureKind; coords: [number, number][] };

/** Overpass QL for major roads + waterways/water in a bbox. */
export function buildQuery(bbox: BBox): string {
  const bb = `${bbox.minLat},${bbox.minLng},${bbox.maxLat},${bbox.maxLng}`; // s,w,n,e
  return `[out:json][timeout:60];
(
  way["highway"~"motorway|trunk|primary"](${bb});
  way["waterway"~"river|canal"](${bb});
  way["natural"="water"](${bb});
);
out geom;`;
}

/** Classify + extract way geometries; ignore non-way / untagged elements. */
export function parseElements(json: { elements?: any[] }): RawFeature[] {
  const out: RawFeature[] = [];
  for (const el of json.elements ?? []) {
    if (el.type !== 'way' || !Array.isArray(el.geometry) || el.geometry.length < 2) continue;
    const tags = el.tags ?? {};
    let kind: FeatureKind | null = null;
    if (tags.highway) kind = 'road';
    else if (tags.waterway || tags.natural === 'water') kind = 'water';
    if (!kind) continue;
    out.push({ kind, coords: el.geometry.map((g: any) => [g.lon, g.lat]) });
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
  return parseElements(await res.json());
}
