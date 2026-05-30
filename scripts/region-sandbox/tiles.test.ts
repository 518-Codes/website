import { expect, test } from 'bun:test';
import { lngLatToTile, bboxTileRange } from './tiles';

test('lngLatToTile maps null island to grid center at zoom 1', () => {
  const { x, y } = lngLatToTile(0, 0, 1);
  expect(x).toBeCloseTo(1, 5);
  expect(y).toBeCloseTo(1, 5);
});

test('lngLatToTile is monotonic: east increases x, north decreases y', () => {
  const a = lngLatToTile(-74, 43, 10);
  const b = lngLatToTile(-73, 42, 10);
  expect(b.x).toBeGreaterThan(a.x);
  expect(b.y).toBeGreaterThan(a.y); // lower latitude => larger y
});

test('bboxTileRange returns inclusive integer tile bounds', () => {
  const r = bboxTileRange({ minLng: -74.1, minLat: 42.5, maxLng: -73.4, maxLat: 43.2 }, 10);
  expect(r.minX).toBeLessThanOrEqual(r.maxX);
  expect(r.minY).toBeLessThanOrEqual(r.maxY);
  expect(Number.isInteger(r.minX)).toBe(true);
  expect(Number.isInteger(r.maxY)).toBe(true);
});
