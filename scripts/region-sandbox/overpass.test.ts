import { expect, test } from 'bun:test';
import { buildQuery, parseElements } from './overpass';

const bbox = { minLng: -74, minLat: 42, maxLng: -73, maxLat: 43 };

test('buildQuery includes the bbox and target tags', () => {
  const q = buildQuery(bbox);
  expect(q).toContain('42,-74,43,-73'); // overpass bbox order: s,w,n,e
  expect(q).toContain('highway');
  expect(q).toContain('waterway');
  expect(q).toContain('secondary');
  expect(q).toContain('stream');
});

test('parseElements classifies motorway as road-major', () => {
  const json = {
    elements: [
      { type: 'way', tags: { highway: 'motorway' }, geometry: [{ lon: -73.5, lat: 42.5 }, { lon: -73.4, lat: 42.6 }] },
    ],
  };
  const out = parseElements(json);
  expect(out).toHaveLength(1);
  expect(out[0].kind).toBe('road-major');
  expect(out[0].coords[0]).toEqual([-73.5, 42.5]);
});

test('parseElements classifies trunk as road-major', () => {
  const json = {
    elements: [
      { type: 'way', tags: { highway: 'trunk' }, geometry: [{ lon: -73.5, lat: 42.5 }, { lon: -73.4, lat: 42.6 }] },
    ],
  };
  const out = parseElements(json);
  expect(out[0].kind).toBe('road-major');
});

test('parseElements classifies motorway_link as road-major', () => {
  const json = {
    elements: [
      { type: 'way', tags: { highway: 'motorway_link' }, geometry: [{ lon: -73.5, lat: 42.5 }, { lon: -73.4, lat: 42.6 }] },
    ],
  };
  const out = parseElements(json);
  expect(out[0].kind).toBe('road-major');
});

test('parseElements classifies primary as road-sub', () => {
  const json = {
    elements: [
      { type: 'way', tags: { highway: 'primary' }, geometry: [{ lon: -73.5, lat: 42.5 }, { lon: -73.4, lat: 42.6 }] },
    ],
  };
  const out = parseElements(json);
  expect(out[0].kind).toBe('road-sub');
});

test('parseElements classifies secondary as road-sub', () => {
  const json = {
    elements: [
      { type: 'way', tags: { highway: 'secondary' }, geometry: [{ lon: -73.5, lat: 42.5 }, { lon: -73.4, lat: 42.6 }] },
    ],
  };
  const out = parseElements(json);
  expect(out[0].kind).toBe('road-sub');
});

test('parseElements classifies primary_link as road-sub', () => {
  const json = {
    elements: [
      { type: 'way', tags: { highway: 'primary_link' }, geometry: [{ lon: -73.5, lat: 42.5 }, { lon: -73.4, lat: 42.6 }] },
    ],
  };
  const out = parseElements(json);
  expect(out[0].kind).toBe('road-sub');
});

test('parseElements classifies secondary_link as road-sub', () => {
  const json = {
    elements: [
      { type: 'way', tags: { highway: 'secondary_link' }, geometry: [{ lon: -73.5, lat: 42.5 }, { lon: -73.4, lat: 42.6 }] },
    ],
  };
  const out = parseElements(json);
  expect(out[0].kind).toBe('road-sub');
});

test('parseElements classifies river as water-line', () => {
  const json = {
    elements: [
      { type: 'way', tags: { waterway: 'river' }, geometry: [{ lon: -73.6, lat: 42.7 }, { lon: -73.6, lat: 42.8 }] },
    ],
  };
  const out = parseElements(json);
  expect(out[0].kind).toBe('water-line');
  expect(out[0].closed).toBeUndefined();
});

test('parseElements classifies natural=water as water-area with closed:true', () => {
  const json = {
    elements: [
      {
        type: 'way',
        tags: { natural: 'water' },
        geometry: [
          { lon: -73.6, lat: 42.7 },
          { lon: -73.5, lat: 42.8 },
          { lon: -73.4, lat: 42.7 },
          { lon: -73.6, lat: 42.7 },
        ],
      },
    ],
  };
  const out = parseElements(json);
  expect(out[0].kind).toBe('water-area');
  expect(out[0].closed).toBe(true);
});

test('parseElements ignores buildings', () => {
  const json = {
    elements: [
      { type: 'way', tags: { building: 'yes' }, geometry: [{ lon: -73.6, lat: 42.7 }, { lon: -73.5, lat: 42.8 }] },
    ],
  };
  const out = parseElements(json);
  expect(out).toHaveLength(0);
});

test('parseElements ignores residential highway', () => {
  const json = {
    elements: [
      { type: 'way', tags: { highway: 'residential' }, geometry: [{ lon: -73.6, lat: 42.7 }, { lon: -73.5, lat: 42.8 }] },
    ],
  };
  const out = parseElements(json);
  expect(out).toHaveLength(0);
});

test('parseElements ignores ways with fewer than 2 geometry points', () => {
  const json = {
    elements: [
      { type: 'way', tags: { highway: 'motorway' }, geometry: [{ lon: -73.5, lat: 42.5 }] },
    ],
  };
  const out = parseElements(json);
  expect(out).toHaveLength(0);
});
