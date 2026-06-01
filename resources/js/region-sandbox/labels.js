import * as THREE from 'three';
import { sampleHeight } from './scene.js';

/**
 * Creates DOM labels for one chunk's cities. World positions account for the chunk's
 * world center; y is recomputed from relief each update so relief changes are reflected.
 *
 * @param {HTMLElement} container the `.region-labels` element
 * @param {{width:number,height:number,data:number[]}} heightmap
 * @param {Array<{name:string,x:number,z:number}>} cities local-normalized coords
 * @param {{ width:number, depth:number, worldX:number, worldZ:number }} opts
 * @returns {{ items: Array, update: (camera: THREE.Camera, w: number, h: number, relief: number) => void, destroy: () => void }}
 */
export function createChunkLabels(container, heightmap, cities, { width, depth, worldX, worldZ }) {
  const items = cities.map((c) => {
    const el = document.createElement('div');
    el.className = 'region-label';
    el.textContent = c.name;
    container.appendChild(el);
    const x = worldX + (c.x - 0.5) * width;
    const z = worldZ + (c.z - 0.5) * depth;
    const nh = sampleHeight(heightmap, c.x, c.z); // normalized height, relief applied at draw time
    return { el, nh, x, z, world: new THREE.Vector3(x, nh, z) };
  });

  const v = new THREE.Vector3();
  const update = function update(camera, w, h, relief) {
    for (const it of items) {
      it.world.set(it.x, it.nh * relief + 0.05, it.z);
      v.copy(it.world).project(camera);
      const behind = v.z > 1;
      it.el.style.display = behind ? 'none' : 'block';
      it.el.style.left = `${(v.x * 0.5 + 0.5) * w}px`;
      it.el.style.top = `${(-v.y * 0.5 + 0.5) * h}px`;
    }
  };

  const destroy = () => {
    for (const it of items) {
      if (it.el.parentNode) { it.el.parentNode.removeChild(it.el); }
    }
  };

  return { items, update, destroy };
}
