// resources/js/region-sandbox/projection.js

/**
 * Global geo→world projection. East is +x, south is +z. Reuses the corridor's
 * isotropic scale (unitsPerDeg) so existing tiles keep their world dimensions.
 *
 * @param {number} lng
 * @param {number} lat
 * @param {{ lngRef:number, latRef:number, unitsPerDeg:number }} projection
 * @returns {{ x:number, z:number }}
 */
export function geoToWorld(lng, lat, projection) {
  const { lngRef, latRef, unitsPerDeg } = projection;
  return { x: (lng - lngRef) * unitsPerDeg, z: (latRef - lat) * unitsPerDeg };
}

/**
 * World placement of a chunk: center {x,z} + size {w,d} (world units).
 *
 * @param {{ minLng:number, minLat:number, maxLng:number, maxLat:number }} bbox
 * @param {{ lngRef:number, latRef:number, unitsPerDeg:number }} projection
 * @returns {{ x:number, z:number, w:number, d:number }}
 */
export function chunkWorld(bbox, projection) {
  const { unitsPerDeg } = projection;
  const c = geoToWorld((bbox.minLng + bbox.maxLng) / 2, (bbox.minLat + bbox.maxLat) / 2, projection);
  return {
    x: c.x,
    z: c.z,
    w: (bbox.maxLng - bbox.minLng) * unitsPerDeg,
    d: (bbox.maxLat - bbox.minLat) * unitsPerDeg,
  };
}

/** Project geo waypoints ({lng,lat}) to world polyline points ({x,z}). */
export function pathPoints(waypoints, projection) {
  return waypoints.map((wp) => geoToWorld(wp.lng, wp.lat, projection));
}

/** Total arc length of a world polyline. */
export function pathLength(points) {
  let len = 0;
  for (let i = 1; i < points.length; i++) {
    len += Math.hypot(points[i].x - points[i - 1].x, points[i].z - points[i - 1].z);
  }
  return len;
}

/** World point at arc-length `s` along the polyline, clamped to [0, length]. */
export function pathPointAt(s, points) {
  if (points.length === 0) { return { x: 0, z: 0 }; }
  if (points.length === 1) { return { x: points[0].x, z: points[0].z }; }
  let rem = Math.min(Math.max(s, 0), pathLength(points));
  for (let i = 1; i < points.length; i++) {
    const a = points[i - 1], b = points[i];
    const seg = Math.hypot(b.x - a.x, b.z - a.z);
    if (rem <= seg || i === points.length - 1) {
      const t = seg === 0 ? 0 : rem / seg;
      return { x: a.x + (b.x - a.x) * t, z: a.z + (b.z - a.z) * t };
    }
    rem -= seg;
  }
  const last = points[points.length - 1];
  return { x: last.x, z: last.z };
}

/** Arc-length `s` of the closest point on the polyline to a world point. */
export function projectPointToS(world, points) {
  let bestD2 = Infinity, bestS = 0, acc = 0;
  for (let i = 1; i < points.length; i++) {
    const a = points[i - 1], b = points[i];
    const vx = b.x - a.x, vz = b.z - a.z;
    const segLen = Math.hypot(vx, vz);
    const denom = vx * vx + vz * vz || 1;
    let t = ((world.x - a.x) * vx + (world.z - a.z) * vz) / denom;
    t = Math.min(1, Math.max(0, t));
    const px = a.x + vx * t, pz = a.z + vz * t;
    const d2 = (world.x - px) ** 2 + (world.z - pz) ** 2;
    if (d2 < bestD2) { bestD2 = d2; bestS = acc + segLen * t; }
    acc += segLen;
  }
  return bestS;
}
