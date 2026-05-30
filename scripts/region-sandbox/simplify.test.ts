import { expect, test } from 'bun:test';
import { simplify, type Pt } from './simplify';

test('simplify drops a near-collinear midpoint', () => {
  const line: Pt[] = [[0, 0], [0.5, 0.0001], [1, 0]];
  const out = simplify(line, 0.01);
  expect(out).toEqual([[0, 0], [1, 0]]);
});

test('simplify keeps a sharp corner', () => {
  const line: Pt[] = [[0, 0], [0.5, 0.5], [1, 0]];
  const out = simplify(line, 0.01);
  expect(out.length).toBe(3);
});

test('simplify passes through lines with < 3 points', () => {
  const line: Pt[] = [[0, 0], [1, 1]];
  expect(simplify(line, 0.1)).toEqual(line);
});
