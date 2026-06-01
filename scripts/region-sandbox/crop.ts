import type { BBox } from './config';
import { lngLatToTile } from './tiles';

export type PixelRect = { x0: number; y0: number; x1: number; y1: number };
export type TileRange = { minX: number; minY: number; maxX: number; maxY: number };

/**
 * Integer pixel rectangle of a bbox within a DEM tile mosaic, relative to the mosaic's
 * top-left tile. The mosaic spans whole tiles (snapped outside the bbox), so the grid
 * must be cropped to this rect before downsampling — otherwise the heightmap is offset
 * from the features (which use the precise bbox) by tens of cells.
 *
 * North (maxLat) is the smaller pixel-y; west (minLng) the smaller pixel-x.
 */
export function bboxPixelRect(bbox: BBox, tileRange: TileRange, z: number, tileSize: number): PixelRect {
  const nw = lngLatToTile(bbox.minLng, bbox.maxLat, z); // north-west
  const se = lngLatToTile(bbox.maxLng, bbox.minLat, z); // south-east
  return {
    x0: Math.round((nw.x - tileRange.minX) * tileSize),
    y0: Math.round((nw.y - tileRange.minY) * tileSize),
    x1: Math.round((se.x - tileRange.minX) * tileSize),
    y1: Math.round((se.y - tileRange.minY) * tileSize),
  };
}

/** Extract a sub-rectangle of a row-major grid, clamped to the source bounds. */
export function cropGrid(
  src: Float32Array,
  sw: number,
  sh: number,
  rect: PixelRect,
): { data: Float32Array; w: number; h: number } {
  const cx0 = Math.max(0, Math.min(sw, rect.x0));
  const cx1 = Math.max(0, Math.min(sw, rect.x1));
  const cy0 = Math.max(0, Math.min(sh, rect.y0));
  const cy1 = Math.max(0, Math.min(sh, rect.y1));
  const w = Math.max(1, cx1 - cx0);
  const h = Math.max(1, cy1 - cy0);
  const out = new Float32Array(w * h);
  for (let y = 0; y < h; y++) {
    for (let x = 0; x < w; x++) {
      out[y * w + x] = src[(cy0 + y) * sw + (cx0 + x)];
    }
  }
  return { data: out, w, h };
}
