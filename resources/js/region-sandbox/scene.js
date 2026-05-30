import * as THREE from 'three';

/** Builds a scene with a heightmap-displaced terrain plane. Returns handles. */
export function createScene(heightmap) {
  const scene = new THREE.Scene();
  scene.background = new THREE.Color(0x0b0f0b);

  const { width, height, data } = heightmap;
  // Plane spans [-1,1] in X and Z; segments = grid cells.
  const geo = new THREE.PlaneGeometry(2, 2 * (height / width), width - 1, height - 1);
  geo.rotateX(-Math.PI / 2); // lay flat: XZ ground plane
  const pos = geo.attributes.position;
  const relief = 0.35; // vertical exaggeration; tune in-browser
  for (let i = 0; i < pos.count; i++) {
    pos.setY(i, data[i] * relief);
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
