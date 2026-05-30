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
    const nh = sampleHeight(heightmap, c.x, c.z); // normalized height, relief applied at draw time
    return { el, nh, world: new THREE.Vector3(x, nh * RELIEF + 0.05, z) };
  });

  const v = new THREE.Vector3();
  const update = function update(camera, w, h) {
    for (const it of items) {
      v.copy(it.world).project(camera);
      const behind = v.z > 1;
      it.el.style.display = behind ? 'none' : 'block';
      it.el.style.left = `${(v.x * 0.5 + 0.5) * w}px`;
      it.el.style.top = `${(-v.y * 0.5 + 0.5) * h}px`;
    }
  };

  const setRelief = (relief) => {
    for (const it of items) {
      it.world.y = it.nh * relief + 0.05;
    }
  };

  return { update, setRelief };
}
