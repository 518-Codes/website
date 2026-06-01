import { expect, test } from 'bun:test';
import { bboxPixelRect, cropGrid } from './crop';

const Z = 10, TILE = 256;

test('bboxPixelRect maps a bbox to mosaic pixels that round-trip back to the bbox edges', () => {
  const bbox = { minLng: -74.3, minLat: 42.868750000000006, maxLng: -73.4, maxLat: 43.2 };
  const n = 2 ** Z;
  const lng2tile = (lng: number) => ((lng + 180) / 360) * n;
  const lat2tile = (lat: number) => {
    const r = (lat * Math.PI) / 180;
    return ((1 - Math.log(Math.tan(r) + 1 / Math.cos(r)) / Math.PI) / 2) * n;
  };
  const range = {
    minX: Math.floor(lng2tile(bbox.minLng)),
    minY: Math.floor(lat2tile(bbox.maxLat)),
    maxX: Math.floor(lng2tile(bbox.maxLng)),
    maxY: Math.floor(lat2tile(bbox.minLat)),
  };
  const rect = bboxPixelRect(bbox, range, Z, TILE);

  // The rect is a strict inset of the mosaic (cropping actually happens — this is the bug fix).
  const mosaicW = (range.maxX - range.minX + 1) * TILE;
  const mosaicH = (range.maxY - range.minY + 1) * TILE;
  expect(rect.x0).toBeGreaterThan(0);
  expect(rect.y0).toBeGreaterThan(0);
  expect(rect.x1).toBeLessThan(mosaicW);
  expect(rect.y1).toBeLessThan(mosaicH);
  expect(rect.x1).toBeGreaterThan(rect.x0);
  expect(rect.y1).toBeGreaterThan(rect.y0);

  // Converting the pixel rect back to lng/lat recovers the bbox edges (the heightmap now
  // registers to the bbox, not to the tile-aligned mosaic).
  const px2lng = (px: number) => ((range.minX + px / TILE) / n) * 360 - 180;
  const px2lat = (py: number) => {
    const m = Math.PI * (1 - (2 * (range.minY + py / TILE)) / n);
    return (180 / Math.PI) * Math.atan(0.5 * (Math.exp(m) - Math.exp(-m)));
  };
  // Accurate to ~1 DEM pixel (integer rounding ≈ 0.0007°), vs the 0.23° bug being fixed.
  expect(px2lng(rect.x0)).toBeCloseTo(bbox.minLng, 2);
  expect(px2lng(rect.x1)).toBeCloseTo(bbox.maxLng, 2);
  expect(px2lat(rect.y0)).toBeCloseTo(bbox.maxLat, 2);
  expect(px2lat(rect.y1)).toBeCloseTo(bbox.minLat, 2);
});

test('cropGrid extracts the requested sub-rectangle row-major', () => {
  // 4x3 source grid:
  //  0  1  2  3
  //  4  5  6  7
  //  8  9 10 11
  const src = new Float32Array([0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]);
  const crop = cropGrid(src, 4, 3, { x0: 1, y0: 1, x1: 3, y1: 3 });
  expect(crop.w).toBe(2);
  expect(crop.h).toBe(2);
  // rows (1,1)-(2,2): [5,6] / [9,10]
  expect(Array.from(crop.data)).toEqual([5, 6, 9, 10]);
});

test('cropGrid clamps a rect that exceeds the source bounds', () => {
  const src = new Float32Array([0, 1, 2, 3]); // 2x2
  const crop = cropGrid(src, 2, 2, { x0: -5, y0: -5, x1: 99, y1: 99 });
  expect(crop.w).toBe(2);
  expect(crop.h).toBe(2);
  expect(Array.from(crop.data)).toEqual([0, 1, 2, 3]);
});
