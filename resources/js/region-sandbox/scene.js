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

/** Sample terrain height (normalized 0..1) at scene-normalized (nx,nz) in [0,1]. */
export function sampleHeight(heightmap, nx, nz) {
  const { width, height, data } = heightmap;
  const cx = Math.min(width - 1, Math.max(0, Math.round(nx * (width - 1))));
  const cz = Math.min(height - 1, Math.max(0, Math.round(nz * (height - 1))));
  return data[cz * width + cx];
}

/**
 * Builds a single chunk's content (terrain mesh + batched features) into a group.
 * Does NOT add the group to the scene — the chunk manager positions it and adds it
 * to the worldGroup. Terrain spans X [-1,1] and Z [-chunkAspect, chunkAspect].
 *
 * @param {{width:number,height:number,data:number[]}} heightmap globally-normalized heights
 * @param {Array} features local-normalized feature coords
 * @param {{ stdMaterial: THREE.Material, elevMaterial: THREE.Material, chunkAspect: number, useElev: boolean }} opts
 * @returns {{ group: THREE.Group, terrain: THREE.Mesh, handles: { roadMajor?: THREE.LineSegments, roadSub?: THREE.LineSegments, waterRiver?: THREE.LineSegments, waterStream?: THREE.LineSegments, waterArea?: THREE.Mesh }, dispose: () => void }}
 */
export function createChunkContent(heightmap, features, { stdMaterial, elevMaterial, chunkAspect, useElev }) {
  const { width, height, data } = heightmap;

  // Plane spans [-1,1] in X and [-chunkAspect, chunkAspect] in Z; segments = grid cells.
  const geo = new THREE.PlaneGeometry(2, 2 * chunkAspect, width - 1, height - 1);
  geo.rotateX(-Math.PI / 2); // lay flat: XZ ground plane
  const pos = geo.attributes.position;
  for (let i = 0; i < pos.count; i++) {
    pos.setY(i, data[i] * RELIEF);
  }
  geo.computeVertexNormals();

  const terrain = new THREE.Mesh(geo, useElev ? elevMaterial : stdMaterial);
  const group = new THREE.Group();
  group.add(terrain);

  const handles = addChunkFeatures(group, heightmap, features, chunkAspect);

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

/**
 * Adds tiered road + water features draped just above the terrain, batched per kind.
 * Coords are local-normalized [0,1]; mapped x=nx*2-1, z=(nz*2-1)*chunkAspect.
 */
function addChunkFeatures(group, heightmap, features, chunkAspect) {
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
          nx * 2 - 1,
          sampleHeight(heightmap, nx, nz) * RELIEF + 0.005,
          (nz * 2 - 1) * chunkAspect,
        );
      }
      for (const [a, b, c] of tris) {
        areaIndices.push(base + a, base + b, base + c);
      }
      continue;
    }

    const target = lineVerts[f.kind];
    if (!target) { continue; }
    const pts = f.coords.map(([nx, nz]) => [
      nx * 2 - 1,
      sampleHeight(heightmap, nx, nz) * RELIEF + 0.01,
      (nz * 2 - 1) * chunkAspect,
    ]);
    for (let i = 0; i + 1 < pts.length; i++) {
      target.push(pts[i][0], pts[i][1], pts[i][2], pts[i + 1][0], pts[i + 1][1], pts[i + 1][2]);
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
