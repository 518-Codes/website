import { expect, test } from 'bun:test';
import {
  EVENT_DEFAULTS,
  recencyDays,
  recencyToSize,
  countdownLabel,
  groupByLocation,
  projectEventToCorridor,
} from './events.js';

const DAY = 86400000;

test('recencyDays is the day delta from now to start', () => {
  expect(recencyDays(1000 + 3 * DAY, 1000)).toBeCloseTo(3, 5);
});

test('recencyToSize hides events beyond the marker horizon', () => {
  expect(recencyToSize(31, EVENT_DEFAULTS).visible).toBe(false);
});

test('recencyToSize is largest now, smallest at the horizon edge', () => {
  const now = recencyToSize(0, EVENT_DEFAULTS);
  const edge = recencyToSize(30, EVENT_DEFAULTS);
  expect(now.visible).toBe(true);
  expect(now.height).toBeGreaterThan(edge.height);
  expect(edge.height).toBeCloseTo(EVENT_DEFAULTS.beaconMinHeight, 5);
});

test('recencyToSize flags + boosts the spotlight band', () => {
  const spot = recencyToSize(1, EVENT_DEFAULTS);
  const noSpot = recencyToSize(5, EVENT_DEFAULTS);
  expect(spot.spotlight).toBe(true);
  expect(noSpot.spotlight).toBe(false);
  // A 1-day event is boosted above its un-boosted linear height.
  const linearAt1 = EVENT_DEFAULTS.beaconMinHeight
    + (EVENT_DEFAULTS.beaconMaxHeight - EVENT_DEFAULTS.beaconMinHeight) * (1 - 1 / 30);
  expect(spot.height).toBeGreaterThan(linearAt1);
});

test('countdownLabel reads naturally', () => {
  expect(countdownLabel(0.4)).toBe('today');
  expect(countdownLabel(1.2)).toBe('tomorrow');
  expect(countdownLabel(3.6)).toBe('in 4 days');
});

test('groupByLocation collapses same-location events, soonest first', () => {
  const groups = groupByLocation([
    { location: 'A', lat: 1, lng: 2, x: 0.4, z: 0.7, startsAtMs: 500 },
    { location: 'A', lat: 1, lng: 2, x: 0.4, z: 0.7, startsAtMs: 200 },
    { location: 'B', lat: 3, lng: 4, x: 0.1, z: 0.2, startsAtMs: 300 },
  ]);
  expect(groups).toHaveLength(2);
  const a = groups.find((g) => g.location === 'A');
  expect(a.events).toHaveLength(2);
  expect(a.soonest.startsAtMs).toBe(200);
  // Chunk-local coords must carry through — Task 6 beacon placement depends on it.
  expect(a.x).toBe(0.4);
  expect(a.z).toBe(0.7);
});

test('projectEventToCorridor (v2): finds containing chunk by bbox, local coords, null when out', () => {
  const manifest = {
    version: 2,
    chunks: [
      // mainline lat-band
      { index: 0, segment: 'mainline', bbox: { minLng: -74.3, minLat: 42.8, maxLng: -73.4, maxLat: 43.2 } },
      // long-island lng-band
      { index: 1, segment: 'longisland', bbox: { minLng: -72.2, minLat: 40.5, maxLng: -71.8, maxLat: 41.2 } },
    ],
  };
  // Inside the mainline chunk: north-west corner → local (0,0).
  const m = projectEventToCorridor({ lat: 43.2, lng: -74.3 }, manifest);
  expect(m.chunkIndex).toBe(0);
  expect(m.x).toBeCloseTo(0, 5);
  expect(m.z).toBeCloseTo(0, 5);
  // Inside the LI chunk (Montauk-ish): south-east → local (1,1).
  const li = projectEventToCorridor({ lat: 40.5, lng: -71.8 }, manifest);
  expect(li.chunkIndex).toBe(1);
  expect(li.x).toBeCloseTo(1, 5);
  expect(li.z).toBeCloseTo(1, 5);
  // Outside every chunk → null.
  expect(projectEventToCorridor({ lat: 50, lng: -74 }, manifest)).toBeNull();
});
