// scripts/region-sandbox/segments.test.ts
import { expect, test } from 'bun:test';
import { chunkBBoxesForSegment, regionOf, chunkGridHeight } from './segments';

test('lat-band segment splits along latitude, north-first', () => {
  const seg = { id: 'mainline', axis: 'lat' as const,
    bbox: { minLng: -74.3, minLat: 42.5, maxLng: -73.4, maxLat: 43.16 }, degPerChunk: 0.33 };
  const bands = chunkBBoxesForSegment(seg);
  expect(bands.length).toBe(2); // 0.66 / 0.33
  expect(bands[0].maxLat).toBeCloseTo(43.16, 6);   // first band is northmost
  expect(bands[0].minLat).toBeCloseTo(42.83, 6);
  expect(bands[1].minLat).toBeCloseTo(42.5, 6);
  expect(bands[0].minLng).toBe(-74.3);             // full lng width preserved
  expect(bands[0].maxLng).toBe(-73.4);
});

test('lng-band segment splits along longitude, west-first', () => {
  const seg = { id: 'longisland', axis: 'lng' as const,
    bbox: { minLng: -74.0, minLat: 40.5, maxLng: -73.0, maxLat: 41.1 }, degPerChunk: 0.5 };
  const bands = chunkBBoxesForSegment(seg);
  expect(bands.length).toBe(2);
  expect(bands[0].minLng).toBeCloseTo(-74.0, 6);   // first band is westmost
  expect(bands[0].maxLng).toBeCloseTo(-73.5, 6);
  expect(bands[0].minLat).toBe(40.5);              // full lat height preserved
  expect(bands[0].maxLat).toBe(41.1);
});

test('regionOf: mainline splits at the boundary lat; longisland is its own region', () => {
  expect(regionOf('mainline', { minLat: 43.4, maxLat: 43.7 } as any, 43.3)).toBe('adirondack');
  expect(regionOf('mainline', { minLat: 42.0, maxLat: 42.3 } as any, 43.3)).toBe('hudson');
  expect(regionOf('longisland', { minLat: 40.6, maxLat: 41.0 } as any, 43.3)).toBe('longisland');
});

test('chunkGridHeight keeps cells ~square (height/width = latSpan/lngSpan)', () => {
  const bbox = { minLng: -74.3, minLat: 42.5, maxLng: -73.4, maxLat: 42.83 };
  expect(chunkGridHeight(bbox, 256)).toBe(Math.round(256 * (0.33 / 0.9)));
});
