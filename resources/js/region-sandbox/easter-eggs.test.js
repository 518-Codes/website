// resources/js/region-sandbox/easter-eggs.test.js
import { expect, test } from 'bun:test';
import * as THREE from 'three';
import { hitTestEggs, createEasterEggs } from './easter-eggs.js';

function eggMesh(id, pos) {
  const m = new THREE.Mesh(new THREE.BoxGeometry(0.2, 1, 0.2), new THREE.MeshBasicMaterial());
  m.position.set(pos.x, pos.y, pos.z);
  m.userData.eggId = id;
  m.updateMatrixWorld(true);
  return m;
}

test('hitTestEggs returns the egg id under the cursor, null otherwise', () => {
  const camera = new THREE.PerspectiveCamera(45, 1, 0.01, 100);
  camera.position.set(0, 0, 5);
  camera.lookAt(0, 0, 0);
  camera.updateMatrixWorld(true);
  const eggs = [eggMesh('marcy', { x: 0, y: 0, z: 0 }), eggMesh('montauk', { x: 3, y: 0, z: 0 })];
  // Centre of screen → ray straight down -Z → hits the egg at origin.
  expect(hitTestEggs({ x: 0, y: 0 }, camera, eggs)).toBe('marcy');
  // Far corner → misses both.
  expect(hitTestEggs({ x: -0.99, y: 0.99 }, camera, eggs)).toBeNull();
});

test('createEasterEggs scales the shaft to the live easterEggSize', () => {
  const parent = new THREE.Group();
  const heightmap = { width: 2, height: 2, data: [0, 0, 0, 0] };
  const thresholds = { easterEggSize: 0.2 };
  const mgr = createEasterEggs(
    parent,
    [{ id: 'x', name: 'X', x: 0, z: 0, w: 1, d: 1, localX: 0.5, localZ: 0.5, heightmap, hue: 0.3 }],
    { thresholds }, // no labelsEl → no DOM needed
  );
  mgr.update(null, 1, 1, 0.35, null);
  const shaft = mgr.items[0].shaft;
  expect(shaft.scale.y).toBeCloseTo(0.2, 6);        // height = size
  expect(shaft.scale.x).toBeCloseTo(0.2 * 0.05, 6); // radius = size * RADIUS_FRAC
  // Live: the slider changes size and the next frame reflects it.
  thresholds.easterEggSize = 0.5;
  mgr.update(null, 1, 1, 0.35, null);
  expect(shaft.scale.y).toBeCloseTo(0.5, 6);
});
