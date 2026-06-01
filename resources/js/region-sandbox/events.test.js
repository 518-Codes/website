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
    { location: 'A', lat: 1, lng: 2, startsAtMs: 500 },
    { location: 'A', lat: 1, lng: 2, startsAtMs: 200 },
    { location: 'B', lat: 3, lng: 4, startsAtMs: 300 },
  ]);
  expect(groups).toHaveLength(2);
  const a = groups.find((g) => g.location === 'A');
  expect(a.events).toHaveLength(2);
  expect(a.soonest.startsAtMs).toBe(200);
});

test('projectEventToCorridor returns chunk + local coords, null when out of bbox', () => {
  const manifest = {
    corridor: { minLng: -74.3, minLat: 40.55, maxLng: -73.4, maxLat: 43.2 },
    chunkCount: 8,
    latSpan: (43.2 - 40.55) / 8,
  };
  // North edge, west edge -> chunk 0, local (0,0).
  const p = projectEventToCorridor({ lat: 43.2, lng: -74.3 }, manifest);
  expect(p.chunkIndex).toBe(0);
  expect(p.x).toBeCloseTo(0, 5);
  expect(p.z).toBeCloseTo(0, 5);
  // Outside the bbox -> null.
  expect(projectEventToCorridor({ lat: 50, lng: -74 }, manifest)).toBeNull();
});
