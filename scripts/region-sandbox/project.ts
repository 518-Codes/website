import type { BBox } from './config';

/**
 * Map a lng/lat to normalized scene coords in [0,1].
 * x grows east; z grows south (so north is z=0), matching a top-down grid
 * whose rows run north→south.
 */
export function projectToScene(lng: number, lat: number, bbox: BBox): { x: number; z: number } {
  const x = (lng - bbox.minLng) / (bbox.maxLng - bbox.minLng);
  const z = (bbox.maxLat - lat) / (bbox.maxLat - bbox.minLat);
  return { x, z };
}
