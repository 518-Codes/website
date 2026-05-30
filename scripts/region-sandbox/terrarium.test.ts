import { expect, test } from 'bun:test';
import { decodeElevation, downsample, normalize } from './terrarium';

test('decodeElevation applies the Terrarium formula', () => {
  // sea level encoding: (128*256 + 0 + 0/256) - 32768 = 0
  expect(decodeElevation(128, 0, 0)).toBeCloseTo(0, 5);
  // +1m: 0.x ... (128*256 + 1) - 32768 = 1
  expect(decodeElevation(128, 1, 0)).toBeCloseTo(1, 5);
});

test('downsample averages a source grid into target dims', () => {
  // 4x4 of all 10s downsampled to 2x2 stays 10
  const src = new Float32Array(16).fill(10);
  const out = downsample(src, 4, 4, 2, 2);
  expect(out.length).toBe(4);
  expect(out.every((v) => Math.abs(v - 10) < 1e-6)).toBe(true);
});

test('downsample box-averages non-uniform 4x2 -> 2x1', () => {
  // row0: 0 2 4 6   row1: 8 10 12 14  (row-major, sw=4 sh=2)
  const src = new Float32Array([0, 2, 4, 6, 8, 10, 12, 14]);
  const out = downsample(src, 4, 2, 2, 1);
  // left cell averages cols0-1 of both rows: (0+2+8+10)/4 = 5
  // right cell averages cols2-3 of both rows: (4+6+12+14)/4 = 9
  expect(out.length).toBe(2);
  expect(out[0]).toBeCloseTo(5, 6);
  expect(out[1]).toBeCloseTo(9, 6);
});

test('normalize maps min->0 and max->1', () => {
  const out = normalize(new Float32Array([100, 200, 300]));
  expect(out[0]).toBeCloseTo(0, 5);
  expect(out[2]).toBeCloseTo(1, 5);
});
