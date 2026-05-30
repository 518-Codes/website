import * as THREE from 'three';
import { RELIEF, sampleHeight } from './scene.js';

/** Creates DOM labels over the canvas and returns an update fn to call each frame. */
export function createLabels(container, heightmap, cities) {
  const aspect = heightmap.height / heightmap.width;
  const items = cities.map((c) => {
    const el = document.createElement('div');
    el.className = 'region-label';
    el.textContent = c.name;
    container.appendChild(el);
    const x = c.x * 2 - 1;
    const z = (c.z * 2 - 1) * aspect;
    const y = sampleHeight(heightmap, c.x, c.z) * RELIEF + 0.05;
    return { el, world: new THREE.Vector3(x, y, z) };
  });

  const v = new THREE.Vector3();
  return function update(camera, w, h) {
    for (const it of items) {
      v.copy(it.world).project(camera);
      const behind = v.z > 1;
      it.el.style.display = behind ? 'none' : 'block';
      it.el.style.left = `${(v.x * 0.5 + 0.5) * w}px`;
      it.el.style.top = `${(-v.y * 0.5 + 0.5) * h}px`;
    }
  };
}
