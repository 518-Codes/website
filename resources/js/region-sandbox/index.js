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

/** Real WebGL capability probe (a truthy global is not enough). */
function webglOk() {
  try {
    const c = document.createElement('canvas');
    return !!(c.getContext('webgl2') || c.getContext('webgl'));
  } catch {
    return false;
  }
}

/** Show the static fallback image inside the panel frame. */
function showFallback(root) {
  root.style.setProperty('--fallback-img', `url("${root.dataset.fallback}")`);
  root.setAttribute('data-unsupported', '');
}

export async function mountRegionSandbox(root) {
  const canvas = root.querySelector('.region-canvas');
  if (!webglOk()) { showFallback(root); return; }

  try {
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
      const isSmall = window.matchMedia('(max-width: 640px)').matches;
      renderer.setPixelRatio(isSmall ? 1 : Math.min(window.devicePixelRatio, 2));
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
  } catch (err) {
    console.error('[region-sandbox] init failed', err);
    showFallback(root);
  }
}
