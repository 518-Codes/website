import { expect, test } from 'bun:test';
import { buildQuery, parseElements } from './overpass';

const bbox = { minLng: -74, minLat: 42, maxLng: -73, maxLat: 43 };

test('buildQuery includes the bbox and target tags', () => {
  const q = buildQuery(bbox);
  expect(q).toContain('42,-74,43,-73'); // overpass bbox order: s,w,n,e
  expect(q).toContain('highway');
  expect(q).toContain('waterway');
});

test('parseElements turns way geometry into typed polylines', () => {
  const json = {
    elements: [
      { type: 'way', tags: { highway: 'motorway' }, geometry: [{ lon: -73.5, lat: 42.5 }, { lon: -73.4, lat: 42.6 }] },
      { type: 'way', tags: { waterway: 'river' }, geometry: [{ lon: -73.6, lat: 42.7 }, { lon: -73.6, lat: 42.8 }] },
      { type: 'way', tags: { building: 'yes' }, geometry: [{ lon: -73.6, lat: 42.7 }] }, // ignored
    ],
  };
  const out = parseElements(json);
  expect(out).toHaveLength(2);
  expect(out[0].kind).toBe('road');
  expect(out[1].kind).toBe('water');
  expect(out[0].coords[0]).toEqual([-73.5, 42.5]);
});
