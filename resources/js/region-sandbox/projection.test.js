import { expect, test } from 'bun:test';
import {
  geoToWorld, chunkWorld, pathPoints, pathLength, pathPointAt, projectPointToS,
} from './projection.js';

const PROJ = { lngRef: -73.85, latRef: 45.0, unitsPerDeg: 2 / 0.9 };

test('geoToWorld: east is +x, south is +z, ref maps to origin-ish', () => {
  const ref = geoToWorld(-73.85, 45.0, PROJ);
  expect(ref.x).toBeCloseTo(0, 6);
  expect(ref.z).toBeCloseTo(0, 6);
  const east = geoToWorld(-72.85, 45.0, PROJ); // +1 deg lng
  expect(east.x).toBeCloseTo(2 / 0.9, 6);
  const south = geoToWorld(-73.85, 44.0, PROJ); // -1 deg lat
  expect(south.z).toBeCloseTo(2 / 0.9, 6);
});

test('chunkWorld: width/depth from span, center from midpoint', () => {
  const w = chunkWorld({ minLng: -74.3, minLat: 42.5, maxLng: -73.4, maxLat: 42.8 }, PROJ);
  expect(w.w).toBeCloseTo(0.9 * (2 / 0.9), 6); // 0.9 deg lng -> exactly 2 units
  expect(w.d).toBeCloseTo(0.3 * (2 / 0.9), 6);
  expect(w.x).toBeCloseTo(geoToWorld(-73.85, 42.65, PROJ).x, 6);
  expect(w.z).toBeCloseTo(geoToWorld(-73.85, 42.65, PROJ).z, 6);
});

test('pathLength sums polyline segments', () => {
  const pts = [{ x: 0, z: 0 }, { x: 0, z: 3 }, { x: 4, z: 3 }];
  expect(pathLength(pts)).toBeCloseTo(7, 6);
});

test('pathPointAt walks the polyline and clamps', () => {
  const pts = [{ x: 0, z: 0 }, { x: 0, z: 3 }, { x: 4, z: 3 }];
  expect(pathPointAt(0, pts)).toEqual({ x: 0, z: 0 });
  expect(pathPointAt(1.5, pts)).toEqual({ x: 0, z: 1.5 });   // mid first leg
  expect(pathPointAt(5, pts)).toEqual({ x: 2, z: 3 });       // 2 into second leg
  expect(pathPointAt(999, pts)).toEqual({ x: 4, z: 3 });     // clamp to end
  expect(pathPointAt(-5, pts)).toEqual({ x: 0, z: 0 });      // clamp to start
});

test('projectPointToS finds arc-length of nearest point on the path', () => {
  const pts = [{ x: 0, z: 0 }, { x: 0, z: 3 }, { x: 4, z: 3 }];
  expect(projectPointToS({ x: 0, z: 1.5 }, pts)).toBeCloseTo(1.5, 6);
  expect(projectPointToS({ x: 2, z: 3.5 }, pts)).toBeCloseTo(5, 6); // off-path, projects onto leg 2
});

test('pathPoints projects geo waypoints', () => {
  const pts = pathPoints([{ lng: -73.85, lat: 45.0 }, { lng: -73.85, lat: 44.0 }], PROJ);
  expect(pts[0]).toEqual({ x: 0, z: 0 });
  expect(pts[1].z).toBeCloseTo(2 / 0.9, 6);
});
