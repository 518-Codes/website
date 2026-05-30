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
    const { scene, group, terrain, key, ambient, dims, stdMaterial, elevMaterial } = createScene(assets.heightmap);
    const { roadLines, waterLines } = addFeatures(group, assets.heightmap, assets.features);

    const labelsEl = root.querySelector('.region-labels');
    const { update: updateLabels, setRelief: setLabelRelief } = createLabels(labelsEl, assets.heightmap, assets.cities);

    const renderer = new THREE.WebGLRenderer({ canvas, antialias: true });

    const aspect = dims.width / dims.height;
    const camera = new THREE.PerspectiveCamera(45, aspect, 0.01, 100);
    const { update: updateControls, setRadius, setTilt, setOrbitSpeed, getValues: getControlValues } = createControls(camera, canvas, dims.height / dims.width);
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

    const api = {
      setRelief(v) { group.scale.y = v / 0.35; setLabelRelief(v); },
      setGlow: ascii.setGlow,
      setCell: ascii.setCell,
      setLight(v) { key.intensity = v; ambient.intensity = v * 0.156; },
      setRadius,
      setTilt,
      setOrbitSpeed,
      setMono: ascii.setMono,
      setElevation(on) {
        terrain.material = on ? elevMaterial : stdMaterial;
        ascii.setElevation(on);
      },
      setLayer(name, on) {
        if (name === 'terrain') { terrain.visible = on; }
        if (name === 'roads' && roadLines) { roadLines.visible = on; }
        if (name === 'water' && waterLines) { waterLines.visible = on; }
      },
      getValues() {
        const a = ascii.getValues();
        const c = getControlValues();
        return {
          relief: group.scale.y * 0.35,
          glow: a.glow,
          cell: a.cell,
          light: key.intensity,
          radius: c.radius,
          tilt: c.tiltDeg,
          orbitSpeed: c.orbitSpeed,
          mono: a.mono,
          elevation: a.elevation,
          roads: roadLines ? roadLines.visible : false,
          water: waterLines ? waterLines.visible : false,
          terrain: terrain.visible,
        };
      },
    };
    return api;
  } catch (err) {
    console.error('[region-sandbox] init failed', err);
    showFallback(root);
  }
}
