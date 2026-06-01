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

const RADIUS_FRAC = 0.05; // shaft radius as a fraction of its height (thin spotlight)
const DEFAULT_SIZE = 0.12;

/**
 * Build the spotlight shafts + invisible hit cylinders for each egg, placed at its
 * world point with its base on the terrain. Eggs persist (not unloaded). The shaft is a
 * unit-height/unit-radius cone scaled per-frame to the live `thresholds.easterEggSize`,
 * so it tracks the size slider (default ≈ the smallest event beacon).
 *
 * @param {THREE.Group} parent worldGroup
 * @param {Array<{id:string, x:number, z:number, w:number, d:number, localX:number, localZ:number, heightmap:object, hue:number}>} placements
 * @param {{ thresholds?: { easterEggSize:number } }} [opts]
 * @returns {{ group: THREE.Group, hitMeshes: THREE.Mesh[], items: Array, update: (relief:number)=>void }}
 */
export function createEasterEggs(parent, placements, { thresholds, labelsEl } = {}) {
  const group = new THREE.Group();
  const hitMeshes = [];
  const items = placements.map((p) => {
    const wx = p.x + (p.localX - 0.5) * p.w;
    const wz = p.z + (p.localZ - 0.5) * p.d;
    const baseNh = sampleHeight(p.heightmap, p.localX, p.localZ); // normalized height
    const color = new THREE.Color().setHSL(p.hue, 1, 0.7);

    // Unit cone (base radius 1, top 0.25, height 1); scaled to size per frame.
    const shaftGeo = new THREE.CylinderGeometry(0.25, 1, 1, 12, 1, true);
    const shaftMat = new THREE.ShaderMaterial({
      uniforms: { uColor: { value: color }, uGlow: { value: 0.8 } },
      vertexShader: SHAFT_VERT, fragmentShader: SHAFT_FRAG, transparent: true,
    });
    const shaft = new THREE.Mesh(shaftGeo, shaftMat);
    group.add(shaft);

    // Generous invisible unit hit cylinder (scaled per frame), so even a tiny shaft is clickable.
    const hit = new THREE.Mesh(
      new THREE.CylinderGeometry(1, 1, 1, 8),
      new THREE.MeshBasicMaterial({ visible: false }),
    );
    hit.userData.eggId = p.id;
    group.add(hit);
    hitMeshes.push(hit);

    // Hover-only HTML label (the spot's name), shown when the cursor is over the egg.
    let el = null;
    if (labelsEl) {
      el = document.createElement('div');
      el.className = 'region-label';
      el.textContent = p.name ?? p.id;
      el.style.display = 'none';
      el.style.pointerEvents = 'none';
      el.style.color = `rgb(${Math.round(color.r * 255)}, ${Math.round(color.g * 255)}, ${Math.round(color.b * 255)})`;
      labelsEl.appendChild(el);
    }

    return { id: p.id, shaft, hit, el, wx, wz, baseNh, world: new THREE.Vector3(wx, 0, wz) };
  });
  parent.add(group);

  const v = new THREE.Vector3();
  const update = (camera, w, h, relief, hoveredId) => {
    const yScale = relief / RELIEF;
    const size = thresholds ? thresholds.easterEggSize : DEFAULT_SIZE;
    const radius = size * RADIUS_FRAC;
    const hitH = Math.max(0.3, size * 1.3);
    const hitR = Math.max(0.06, radius * 5);
    for (const it of items) {
      const baseY = it.baseNh * RELIEF * yScale;
      it.shaft.scale.set(radius, size, radius);
      it.shaft.position.set(it.wx, baseY + size / 2, it.wz);
      it.hit.scale.set(hitR, hitH, hitR);
      it.hit.position.set(it.wx, baseY + hitH / 2, it.wz);

      if (it.el) {
        if (hoveredId === it.id && camera) {
          it.world.set(it.wx, baseY + size + 0.04, it.wz);
          v.copy(it.world).project(camera);
          const behind = v.z > 1;
          it.el.style.display = behind ? 'none' : 'block';
          it.el.style.left = `${(v.x * 0.5 + 0.5) * w}px`;
          it.el.style.top = `${(-v.y * 0.5 + 0.5) * h}px`;
        } else {
          it.el.style.display = 'none';
        }
      }
    }
  };

  return { group, hitMeshes, items, update };
}
