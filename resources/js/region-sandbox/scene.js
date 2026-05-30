import * as THREE from 'three';

/** Vertical exaggeration applied to normalized heights; shared by terrain, features, labels. */
export const RELIEF = 0.35;

/** Builds a scene with a heightmap-displaced terrain plane. Returns handles. */
export function createScene(heightmap) {
  const scene = new THREE.Scene();
  scene.background = new THREE.Color(0x0b0f0b);

  const { width, height, data } = heightmap;
  // Plane spans [-1,1] in X and Z; segments = grid cells.
  const geo = new THREE.PlaneGeometry(2, 2 * (height / width), width - 1, height - 1);
  geo.rotateX(-Math.PI / 2); // lay flat: XZ ground plane
  const pos = geo.attributes.position;
  for (let i = 0; i < pos.count; i++) {
    pos.setY(i, data[i] * RELIEF);
  }
  geo.computeVertexNormals();

  const mat = new THREE.MeshStandardMaterial({ color: 0x2a3a2a, flatShading: false, metalness: 0, roughness: 1 });
  const terrain = new THREE.Mesh(geo, mat);
  scene.add(terrain);

  // Lighting tuned so elevation reads as luminance (the ASCII pass keys off brightness).
  const key = new THREE.DirectionalLight(0xffffff, 1.6);
  key.position.set(-1.5, 2, 1);
  scene.add(key);
  scene.add(new THREE.AmbientLight(0xffffff, 0.25));

  return { scene, terrain, dims: { width, height } };
}

/** Sample terrain height (normalized 0..1) at scene-normalized (nx,nz) in [0,1]. */
export function sampleHeight(heightmap, nx, nz) {
  const { width, height, data } = heightmap;
  const cx = Math.min(width - 1, Math.max(0, Math.round(nx * (width - 1))));
  const cz = Math.min(height - 1, Math.max(0, Math.round(nz * (height - 1))));
  return data[cz * width + cx];
}

/**
 * Adds road + river polylines draped just above the terrain. Batches ALL roads
 * into one LineSegments and ALL water into another (2 draw calls total) rather
 * than one Line per feature.
 */
export function addFeatures(scene, heightmap, features) {
  const aspect = heightmap.height / heightmap.width;
  const roadVerts = [];
  const waterVerts = [];

  for (const f of features) {
    const pts = f.coords.map(([nx, nz]) => [
      nx * 2 - 1,
      sampleHeight(heightmap, nx, nz) * RELIEF + 0.01,
      (nz * 2 - 1) * aspect,
    ]);
    const target = f.kind === 'water' ? waterVerts : roadVerts;
    for (let i = 0; i + 1 < pts.length; i++) {
      target.push(pts[i][0], pts[i][1], pts[i][2], pts[i + 1][0], pts[i + 1][1], pts[i + 1][2]);
    }
  }

  const addBatch = (verts, color) => {
    if (verts.length === 0) return;
    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.Float32BufferAttribute(verts, 3));
    scene.add(new THREE.LineSegments(geo, new THREE.LineBasicMaterial({ color })));
  };

  addBatch(roadVerts, 0xbfffd0);  // brighter => denser glyphs in the ASCII pass
  addBatch(waterVerts, 0x8fe0ff);
}
