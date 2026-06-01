import * as THREE from 'three';

/** Vertical exaggeration applied to normalized heights; shared by terrain, features, labels. */
export const RELIEF = 0.35;

/** Base (pre-glow) colors per feature layer; shared by the renderer and the glow controls. */
export const FEATURE_COLORS = {
  'road-major': 0xd8ffe0, // brightest => dominant glyphs
  'road-sub': 0x9fe8b5, // dimmer green
  'water-river': 0xbfeeff, // bright blue, prominent
  'water-stream': 0x4f93b0, // dim/muted blue, recedes
  'water-area': 0x9af2ff, // bright cyan fill
};
export const TERRAIN_COLOR = 0x2a3a2a;
export const TERRAIN_GREEN = 0x5efc8d;

/**
 * Builds the shared world: scene lighting + shared terrain materials + a worldGroup.
 * All chunk groups are added inside worldGroup so worldGroup.scale.y controls relief globally.
 *
 * @returns {{ key: THREE.DirectionalLight, ambient: THREE.AmbientLight, stdMaterial: THREE.MeshStandardMaterial, elevMaterial: THREE.ShaderMaterial, worldGroup: THREE.Group }}
 */
export function createWorld(scene) {
  scene.background = new THREE.Color(0x0b0f0b);

  const stdMaterial = new THREE.MeshStandardMaterial({ color: TERRAIN_COLOR, flatShading: false, metalness: 0, roughness: 1 });
  // Unlit material that shades terrain by normalized height in discrete green bands.
  const elevMaterial = new THREE.ShaderMaterial({
    uniforms: {
      uRelief: { value: RELIEF },
      uBands: { value: 12.0 },
      uCurve: { value: 0.5 },
      uGreen: { value: new THREE.Color(TERRAIN_GREEN) },
    },
    vertexShader: `
      varying float vH;
      void main(){
        vH = position.y;
        gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
      }`,
    fragmentShader: `
      precision highp float;
      varying float vH;
      uniform float uRelief, uBands, uCurve;
      uniform vec3 uGreen;
      void main(){
        float nh = clamp(vH / uRelief, 0.0, 1.0);
        float t = pow(nh, uCurve);                       // expand low elevations -> finer banding down low
        float band = floor(t * uBands) / (uBands - 1.0);
        gl_FragColor = vec4(uGreen * band * 0.8, 1.0);
      }`,
  });

  // All chunk groups live inside worldGroup so relief can be tuned via worldGroup.scale.y.
  const worldGroup = new THREE.Group();
  scene.add(worldGroup);

  // Lighting tuned so elevation reads as luminance (the ASCII pass keys off brightness).
  const key = new THREE.DirectionalLight(0xffffff, 1.6);
  key.position.set(-1.5, 2, 1);
  scene.add(key);
  const ambient = new THREE.AmbientLight(0xffffff, 0.25);
  scene.add(ambient);

  return { key, ambient, stdMaterial, elevMaterial, worldGroup };
}

/**
 * Sample terrain height (normalized 0..1) at scene-normalized (nx,nz) in [0,1].
 * Bilinear, so draped features hug the interpolated terrain surface instead of
 * snapping to the nearest grid cell (nearest-neighbor floats lines off slopes).
 */
export function sampleHeight(heightmap, nx, nz) {
  const { width, height, data } = heightmap;
  const fx = Math.min(width - 1, Math.max(0, nx * (width - 1)));
  const fz = Math.min(height - 1, Math.max(0, nz * (height - 1)));
  const x0 = Math.floor(fx), z0 = Math.floor(fz);
  const x1 = Math.min(width - 1, x0 + 1), z1 = Math.min(height - 1, z0 + 1);
  const tx = fx - x0, tz = fz - z0;
  const h00 = data[z0 * width + x0], h10 = data[z0 * width + x1];
  const h01 = data[z1 * width + x0], h11 = data[z1 * width + x1];
  const top = h00 + (h10 - h00) * tx;
  const bottom = h01 + (h11 - h01) * tx;
  return top + (bottom - top) * tz;
}

/**
 * Clip a 2-D segment a→b (each `[nx, nz]`) to the unit box [0,1]². Returns the clipped
 * `[start, end]` endpoints, or null if the segment is entirely outside.
 *
 * Features are baked with full way geometry that overruns the chunk's band, so a road
 * near a boundary lands in both adjacent chunks; the out-of-band copy gets a clamped
 * (wrong) draped height and renders as a ghost. Clipping makes each chunk draw only the
 * portion it owns. (Liang–Barsky.)
 */
export function clipSegmentUnitBox(a, b) {
  let t0 = 0, t1 = 1;
  const dx = b[0] - a[0], dz = b[1] - a[1];
  const p = [-dx, dx, -dz, dz];
  const q = [a[0], 1 - a[0], a[1], 1 - a[1]];
  for (let i = 0; i < 4; i++) {
    if (p[i] === 0) {
      if (q[i] < 0) { return null; } // parallel to an edge and outside it
    } else {
      const r = q[i] / p[i];
      if (p[i] < 0) {
        if (r > t1) { return null; }
        if (r > t0) { t0 = r; }
      } else {
        if (r < t0) { return null; }
        if (r < t1) { t1 = r; }
      }
    }
  }
  return [
    [a[0] + t0 * dx, a[1] + t0 * dz],
    [a[0] + t1 * dx, a[1] + t1 * dz],
  ];
}

/**
 * Builds a single chunk's content (terrain mesh + batched features) into a group.
 * Does NOT add the group to the scene — the chunk manager positions it and adds it
 * to the worldGroup. Geometry is centered on the local origin.
 *
 * @param {{width:number,height:number,data:number[]}} heightmap globally-normalized heights
 * @param {Array} features local-normalized feature coords
 * @param {{ stdMaterial: THREE.Material, elevMaterial: THREE.Material, width: number, depth: number, useElev: boolean }} opts
 * @returns {{ group: THREE.Group, terrain: THREE.Mesh, handles: { roadMajor?: THREE.LineSegments, roadSub?: THREE.LineSegments, waterRiver?: THREE.LineSegments, waterStream?: THREE.LineSegments, waterArea?: THREE.Mesh }, dispose: () => void }}
 */
export function createChunkContent(heightmap, features, { stdMaterial, elevMaterial, width: worldW, depth: worldD, useElev }) {
  const { width, height, data } = heightmap;

  // Plane spans worldW in X and worldD in Z, centered on local origin; segments = grid cells.
  const geo = new THREE.PlaneGeometry(worldW, worldD, width - 1, height - 1);
  geo.rotateX(-Math.PI / 2); // lay flat: XZ ground plane
  const pos = geo.attributes.position;
  for (let i = 0; i < pos.count; i++) {
    pos.setY(i, data[i] * RELIEF);
  }
  geo.computeVertexNormals();

  const terrain = new THREE.Mesh(geo, useElev ? elevMaterial : stdMaterial);
  const group = new THREE.Group();
  group.add(terrain);

  const handles = addChunkFeatures(group, heightmap, features, worldW, worldD);

  // Geometries created for this chunk, for disposal.
  const geometries = [geo];
  for (const h of Object.values(handles)) {
    if (h) { geometries.push(h.geometry); }
  }

  const dispose = () => {
    for (const g of geometries) { g.dispose(); }
    // Line/area materials are per-chunk (created below); free them too.
    for (const h of Object.values(handles)) {
      if (h && h.material) { h.material.dispose(); }
    }
  };

  return { group, terrain, handles, dispose };
}

/** Map a local-normalized line vertex [nx,nz] to draped world [x,y,z]. */
function mapFeaturePoint([nx, nz], heightmap, worldW, worldD) {
  return [
    (nx - 0.5) * worldW,
    sampleHeight(heightmap, nx, nz) * RELIEF + 0.01,
    (nz - 0.5) * worldD,
  ];
}

/**
 * Adds tiered road + water features draped just above the terrain, batched per kind.
 * Coords are local-normalized [0,1]; mapped x=(nx-0.5)*worldW, z=(nz-0.5)*worldD.
 */
function addChunkFeatures(group, heightmap, features, worldW, worldD) {
  const lineVerts = { 'road-major': [], 'road-sub': [], 'water-river': [], 'water-stream': [] };

  const areaPositions = [];
  const areaIndices = [];
  let areaSkipped = 0;

  for (const f of features) {
    if (f.kind === 'water-area' && f.closed) {
      let ring = f.coords;
      if (ring.length > 1) {
        const first = ring[0];
        const last = ring[ring.length - 1];
        if (first[0] === last[0] && first[1] === last[1]) {
          ring = ring.slice(0, -1);
        }
      }
      if (ring.length < 3) { areaSkipped++; continue; }

      // Each area is owned by the chunk containing its centroid, so a ring straddling a
      // boundary is drawn once (by its owner) instead of ghosting in both neighbours.
      let cx = 0, cz = 0;
      for (const [nx, nz] of ring) { cx += nx; cz += nz; }
      cx /= ring.length; cz /= ring.length;
      if (cx < 0 || cx > 1 || cz < 0 || cz > 1) { continue; }

      const contour = ring.map(([nx, nz]) => new THREE.Vector2(nx, nz));
      let tris;
      try {
        tris = THREE.ShapeUtils.triangulateShape(contour, []);
      } catch {
        areaSkipped++;
        continue;
      }
      if (!tris || tris.length === 0) { areaSkipped++; continue; }

      const base = areaPositions.length / 3;
      for (const [nx, nz] of ring) {
        areaPositions.push(
          (nx - 0.5) * worldW,
          sampleHeight(heightmap, nx, nz) * RELIEF + 0.005,
          (nz - 0.5) * worldD,
        );
      }
      for (const [a, b, c] of tris) {
        areaIndices.push(base + a, base + b, base + c);
      }
      continue;
    }

    const target = lineVerts[f.kind];
    if (!target) { continue; }
    // Clip each segment to the chunk's [0,1] band so only the owned portion is drawn
    // (and its draped height is sampled in-range, not clamped to a wrong edge value).
    for (let i = 0; i + 1 < f.coords.length; i++) {
      const seg = clipSegmentUnitBox(f.coords[i], f.coords[i + 1]);
      if (!seg) { continue; }
      const a = mapFeaturePoint(seg[0], heightmap, worldW, worldD);
      const b = mapFeaturePoint(seg[1], heightmap, worldW, worldD);
      target.push(a[0], a[1], a[2], b[0], b[1], b[2]);
    }
  }

  const addLineBatch = (verts, color) => {
    if (verts.length === 0) { return undefined; }
    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.Float32BufferAttribute(verts, 3));
    const lines = new THREE.LineSegments(geo, new THREE.LineBasicMaterial({ color }));
    group.add(lines);
    return lines;
  };

  const roadMajor = addLineBatch(lineVerts['road-major'], FEATURE_COLORS['road-major']);
  const roadSub = addLineBatch(lineVerts['road-sub'], FEATURE_COLORS['road-sub']);
  const waterRiver = addLineBatch(lineVerts['water-river'], FEATURE_COLORS['water-river']);
  const waterStream = addLineBatch(lineVerts['water-stream'], FEATURE_COLORS['water-stream']);

  let waterArea;
  if (areaIndices.length > 0) {
    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.Float32BufferAttribute(areaPositions, 3));
    geo.setIndex(areaIndices);
    waterArea = new THREE.Mesh(geo, new THREE.MeshBasicMaterial({ color: FEATURE_COLORS['water-area'], side: THREE.DoubleSide }));
    group.add(waterArea);
  }

  if (areaSkipped > 0) {
    console.warn(`[region-sandbox] skipped ${areaSkipped} water-area ring(s) (triangulation failed or < 3 points)`);
  }

  return { roadMajor, roadSub, waterRiver, waterStream, waterArea };
}
