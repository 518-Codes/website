import { expect, test } from 'bun:test';
import { sampleHeight, clipSegmentUnitBox } from './scene.js';

// 2x2 grid, row-major data[z*width + x]:
//   (0,0)=0  (1,0)=1
//   (0,1)=2  (1,1)=3
const HM = { width: 2, height: 2, data: [0, 1, 2, 3] };

test('sampleHeight returns exact values at grid corners', () => {
  expect(sampleHeight(HM, 0, 0)).toBeCloseTo(0, 6);
  expect(sampleHeight(HM, 1, 0)).toBeCloseTo(1, 6);
  expect(sampleHeight(HM, 0, 1)).toBeCloseTo(2, 6);
  expect(sampleHeight(HM, 1, 1)).toBeCloseTo(3, 6);
});

test('sampleHeight bilinearly interpolates between grid cells', () => {
  expect(sampleHeight(HM, 0.5, 0.5)).toBeCloseTo(1.5, 6); // mean of all four
  expect(sampleHeight(HM, 0.5, 0)).toBeCloseTo(0.5, 6);   // mid top edge
  expect(sampleHeight(HM, 0, 0.5)).toBeCloseTo(1.0, 6);   // mid left edge
  expect(sampleHeight(HM, 0.25, 0)).toBeCloseTo(0.25, 6); // quarter along top
});

test('sampleHeight clamps out-of-range coords to the edge', () => {
  expect(sampleHeight(HM, -1, -1)).toBeCloseTo(0, 6);
  expect(sampleHeight(HM, 2, 2)).toBeCloseTo(3, 6);
});

test('clipSegmentUnitBox keeps a fully-inside segment', () => {
  const r = clipSegmentUnitBox([0.2, 0.3], [0.8, 0.7]);
  expect(r[0][0]).toBeCloseTo(0.2, 6);
  expect(r[0][1]).toBeCloseTo(0.3, 6);
  expect(r[1][0]).toBeCloseTo(0.8, 6);
  expect(r[1][1]).toBeCloseTo(0.7, 6);
});

test('clipSegmentUnitBox drops a fully-outside segment', () => {
  expect(clipSegmentUnitBox([1.2, 0.5], [2.0, 0.5])).toBeNull();
  expect(clipSegmentUnitBox([0.5, -0.2], [0.5, -1.0])).toBeNull();
});

test('clipSegmentUnitBox trims a segment crossing the south edge (nz=1)', () => {
  const r = clipSegmentUnitBox([0.5, 0.5], [0.5, 2.0]); // into the chunk below
  expect(r[0][1]).toBeCloseTo(0.5, 6);
  expect(r[1][1]).toBeCloseTo(1, 6);
});

test('clipSegmentUnitBox trims both ends of a segment spanning the box', () => {
  const r = clipSegmentUnitBox([-1, 0.4], [2, 0.4]); // horizontal, overruns both sides
  expect(r[0][0]).toBeCloseTo(0, 6);
  expect(r[1][0]).toBeCloseTo(1, 6);
});
