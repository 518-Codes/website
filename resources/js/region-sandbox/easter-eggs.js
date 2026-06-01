// resources/js/region-sandbox/easter-eggs.js
import * as THREE from 'three';
import { sampleHeight, RELIEF } from './scene.js';

const raycaster = new THREE.Raycaster();

/**
 * Raycast NDC ({x,y} in [-1,1]) against egg hit meshes. Returns the hit egg's
 * userData.eggId (nearest), or null.
 */
export function hitTestEggs(ndc, camera, eggMeshes) {
  raycaster.setFromCamera(ndc, camera);
  const hits = raycaster.intersectObjects(eggMeshes, false);
  return hits.length ? hits[0].object.userData.eggId : null;
}

// Subtle base-bright→top-dim shaft; mirrors the beacon shader idiom (renders cleanly
// through the ASCII pass).
const SHAFT_VERT = `
  varying float vT;
  void main(){ vT = position.y + 0.5; gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0); }`;
const SHAFT_FRAG = `
  precision highp float;
  uniform vec3 uColor; uniform float uGlow;
  varying float vT;
  void main(){ float b = 1.0 - 0.85 * vT; gl_FragColor = vec4(uColor * b * uGlow, 1.0); }`;

/**
 * Build the spotlight shafts + invisible hit cylinders for each egg, placed at its
 * world point with its base on the terrain. Eggs persist (not unloaded).
 *
 * @param {THREE.Group} parent worldGroup
 * @param {Array<{id:string, x:number, z:number, w:number, d:number, localX:number, localZ:number, heightmap:object, hue:number}>} placements
 * @returns {{ group: THREE.Group, hitMeshes: THREE.Mesh[], update: (relief:number)=>void }}
 */
export function createEasterEggs(parent, placements) {
  const group = new THREE.Group();
  const hitMeshes = [];
  const SHAFT_H = 0.6;
  const items = placements.map((p) => {
    const wx = p.x + (p.localX - 0.5) * p.w;
    const wz = p.z + (p.localZ - 0.5) * p.d;
    const baseNh = sampleHeight(p.heightmap, p.localX, p.localZ); // normalized height
    const color = new THREE.Color().setHSL(p.hue, 1, 0.7);

    const shaftGeo = new THREE.CylinderGeometry(0.012, 0.03, 1, 12, 1, true);
    const shaftMat = new THREE.ShaderMaterial({
      uniforms: { uColor: { value: color }, uGlow: { value: 0.8 } },
      vertexShader: SHAFT_VERT, fragmentShader: SHAFT_FRAG, transparent: true,
    });
    const shaft = new THREE.Mesh(shaftGeo, shaftMat);
    group.add(shaft);

    // Generous invisible hit cylinder co-located with the shaft.
    const hit = new THREE.Mesh(
      new THREE.CylinderGeometry(0.08, 0.08, SHAFT_H, 8),
      new THREE.MeshBasicMaterial({ visible: false }),
    );
    hit.userData.eggId = p.id;
    group.add(hit);
    hitMeshes.push(hit);

    return { shaft, hit, wx, wz, baseNh, world: new THREE.Vector3(wx, 0, wz) };
  });
  parent.add(group);

  const update = (relief) => {
    const yScale = relief / RELIEF;
    for (const it of items) {
      const baseY = it.baseNh * RELIEF * yScale;
      it.shaft.scale.set(1, SHAFT_H, 1);
      it.shaft.position.set(it.wx, baseY + SHAFT_H / 2, it.wz);
      it.hit.position.set(it.wx, baseY + SHAFT_H / 2, it.wz);
    }
  };

  return { group, hitMeshes, items, update };
}
