import { expect, test } from 'bun:test';
import { projectToScene } from './project';

const bbox = { minLng: -74, minLat: 42, maxLng: -73, maxLat: 43 };

test('projectToScene maps SW corner to (0,1) and NE to (1,0)', () => {
  expect(projectToScene(-74, 42, bbox)).toEqual({ x: 0, z: 1 });
  expect(projectToScene(-73, 43, bbox)).toEqual({ x: 1, z: 0 });
});

test('projectToScene puts the center at (0.5, 0.5)', () => {
  const p = projectToScene(-73.5, 42.5, bbox);
  expect(p.x).toBeCloseTo(0.5, 6);
  expect(p.z).toBeCloseTo(0.5, 6);
});
