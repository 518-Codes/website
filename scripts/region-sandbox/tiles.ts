import type { BBox } from './config';

/** Fractional slippy tile coordinate for a lng/lat at zoom z (Web Mercator). */
export function lngLatToTile(lng: number, lat: number, z: number): { x: number; y: number } {
  const n = 2 ** z;
  const x = ((lng + 180) / 360) * n;
  const latRad = (lat * Math.PI) / 180;
  const y = ((1 - Math.log(Math.tan(latRad) + 1 / Math.cos(latRad)) / Math.PI) / 2) * n;
  return { x, y };
}

/** Inclusive integer tile range covering a bbox at zoom z. */
export function bboxTileRange(bbox: BBox, z: number): { minX: number; minY: number; maxX: number; maxY: number } {
  const nw = lngLatToTile(bbox.minLng, bbox.maxLat, z); // north-west
  const se = lngLatToTile(bbox.maxLng, bbox.minLat, z); // south-east
  return {
    minX: Math.floor(nw.x),
    minY: Math.floor(nw.y),
    maxX: Math.floor(se.x),
    maxY: Math.floor(se.y),
  };
}
