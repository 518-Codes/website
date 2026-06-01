// scripts/region-sandbox/segments.ts
import type { BBox } from './config';

export type Segment = { id: string; axis: 'lat' | 'lng'; bbox: BBox; degPerChunk: number };

/** Split a segment's bbox into chunk bboxes along its axis. lat→north-first, lng→west-first. */
export function chunkBBoxesForSegment(seg: Segment): BBox[] {
  const { bbox, axis, degPerChunk } = seg;
  const span = axis === 'lat' ? bbox.maxLat - bbox.minLat : bbox.maxLng - bbox.minLng;
  const n = Math.max(1, Math.round(span / degPerChunk));
  const step = span / n;
  const out: BBox[] = [];
  for (let i = 0; i < n; i++) {
    if (axis === 'lat') {
      const maxLat = bbox.maxLat - i * step;       // north-first
      out.push({ minLng: bbox.minLng, maxLng: bbox.maxLng, minLat: maxLat - step, maxLat });
    } else {
      const minLng = bbox.minLng + i * step;        // west-first
      out.push({ minLat: bbox.minLat, maxLat: bbox.maxLat, minLng, maxLng: minLng + step });
    }
  }
  return out;
}

/** Region id for a chunk: mainline splits at boundaryLat; everything else is its segment id. */
export function regionOf(segmentId: string, bbox: BBox, boundaryLat: number): string {
  if (segmentId !== 'mainline') { return segmentId; }
  return bbox.minLat >= boundaryLat ? 'adirondack' : 'hudson';
}

/** Grid height that keeps cells ~square for a given grid width. */
export function chunkGridHeight(bbox: BBox, gridW: number): number {
  const latSpan = bbox.maxLat - bbox.minLat;
  const lngSpan = bbox.maxLng - bbox.minLng;
  return Math.round(gridW * (latSpan / lngSpan));
}
