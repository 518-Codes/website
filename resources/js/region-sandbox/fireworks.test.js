// resources/js/region-sandbox/fireworks.test.js
import { expect, test } from 'bun:test';
import { fireworkParticleAt } from './fireworks.js';

test('fireworkParticleAt: position is p0 + vel*t - gravity', () => {
  const p = fireworkParticleAt({ x: 1, y: 2, z: 3 }, { x: 1, y: 4, z: 0 }, 9.8, 0.5);
  expect(p.x).toBeCloseTo(1 + 1 * 0.5, 6);
  expect(p.y).toBeCloseTo(2 + 4 * 0.5 - 0.5 * 9.8 * 0.25, 6);
  expect(p.z).toBeCloseTo(3, 6);
});

test('fireworkParticleAt: t=0 is the origin', () => {
  const p = fireworkParticleAt({ x: 5, y: 6, z: 7 }, { x: 9, y: 9, z: 9 }, 9.8, 0);
  expect(p).toEqual({ x: 5, y: 6, z: 7 });
});
