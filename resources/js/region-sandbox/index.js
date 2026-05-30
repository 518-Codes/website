import * as THREE from 'three';
import { createScene, addFeatures } from './scene.js';
import { createLabels } from './labels.js';
import { createControls } from './controls.js';
import { createAsciiPass } from './ascii-pass.js';

async function loadAssets(base) {
  const [heightmap, features, cities] = await Promise.all([
    fetch(`${base}/heightmap.json`).then((r) => r.json()),
    fetch(`${base}/features.json`).then((r) => r.json()),
    fetch(`${base}/cities.json`).then((r) => r.json()),
  ]);
  return { heightmap, features, cities };
}

export async function mountRegionSandbox(root) {
  const canvas = root.querySelector('.region-canvas');
  if (!window.WebGLRenderingContext) { root.setAttribute('data-unsupported', ''); return; }

  const assets = await loadAssets(root.dataset.assets);
  const { scene, dims } = createScene(assets.heightmap);
  addFeatures(scene, assets.heightmap, assets.features);

  const labelsEl = root.querySelector('.region-labels');
  const updateLabels = createLabels(labelsEl, assets.heightmap, assets.cities);

  const renderer = new THREE.WebGLRenderer({ canvas, antialias: true });

  const aspect = dims.width / dims.height;
  const camera = new THREE.PerspectiveCamera(45, aspect, 0.01, 100);
  const updateControls = createControls(camera, canvas, dims.height / dims.width);
  const ascii = createAsciiPass(renderer, scene, camera);

  const resize = () => {
    const w = canvas.clientWidth, h = canvas.clientHeight;
    renderer.setSize(w, h, false);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    ascii.resize(w, h);
  };
  resize();
  window.addEventListener('resize', resize);

  let raf = 0;
  const tick = () => {
    raf = requestAnimationFrame(tick);
    updateControls();
    updateLabels(camera, canvas.clientWidth, canvas.clientHeight);
    ascii.render();
  };
  tick();

  // teardown on visibility loss
  const vis = new IntersectionObserver(([e]) => {
    if (e.isIntersecting && !raf) tick();
    else if (!e.isIntersecting && raf) { cancelAnimationFrame(raf); raf = 0; }
  }, { threshold: 0 });
  vis.observe(root);
}
