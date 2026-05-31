import * as THREE from 'three';
import { createWorld, createChunkContent } from './scene.js';
import { createChunkLabels } from './labels.js';
import { createControls } from './controls.js';
import { createAsciiPass } from './ascii-pass.js';

/** Maps a layer name to its handle key on createChunkContent's `handles`. */
const LAYER_HANDLE = {
  'road-major': 'roadMajor',
  'road-sub': 'roadSub',
  'water-river': 'waterRiver',
  'water-stream': 'waterStream',
  'water-area': 'waterArea',
};

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
    const base = root.dataset.assets;
    const manifest = await fetch(`${base}/corridor/manifest.json`).then((r) => r.json());

    const chunkCount = manifest.chunkCount;
    const chunkAspect = manifest.chunkAspect;
    const chunkZSpan = 2 * chunkAspect;
    // Corridor world-z range: north edge -chunkAspect, south edge (2*(N-1)+1)*chunkAspect.
    const corridorMinZ = -chunkAspect;
    const corridorMaxZ = (2 * (chunkCount - 1) + 1) * chunkAspect;

    const scene = new THREE.Scene();
    const { key, ambient, stdMaterial, elevMaterial, worldGroup } = createWorld(scene);

    const labelsEl = root.querySelector('.region-labels');

    const renderer = new THREE.WebGLRenderer({ canvas, antialias: true });

    // Grid is square-ish per chunk (256 x 94); camera aspect comes from the canvas at resize.
    const camera = new THREE.PerspectiveCamera(45, 1, 0.01, 100);
    const controls = createControls(camera, canvas, chunkAspect);
    controls.setScrubBounds(corridorMinZ, corridorMaxZ);
    controls.setTarget(chunkAspect); // frame the Capital Region (chunks 0-1) initially
    const ascii = createAsciiPass(renderer, scene, camera);

    // Current control state shared across all chunks; new chunks inherit it on load.
    const settings = {
      relief: 0.35,
      light: 1.6,
      elevationOn: false,
      layers: {
        'road-major': true,
        'road-sub': true,
        'water-river': true,
        'water-stream': false, // streams hidden by default (declutter)
        'water-area': true,
        terrain: true,
      },
    };

    /** index -> { content, labels } */
    const loaded = new Map();
    const loading = new Set();

    /** Apply current layer visibility (from settings) to a chunk's content. */
    const applyLayers = (content) => {
      content.terrain.visible = settings.layers.terrain;
      for (const [name, handleKey] of Object.entries(LAYER_HANDLE)) {
        const h = content.handles[handleKey];
        if (h) { h.visible = settings.layers[name]; }
      }
    };

    async function loadChunk(i) {
      if (i < 0 || i >= chunkCount) { return; }
      if (loaded.has(i) || loading.has(i)) { return; }
      loading.add(i);
      try {
        const meta = manifest.chunks[i];
        const chunk = await fetch(`${base}/corridor/${meta.file}`).then((r) => r.json());
        // A concurrent caller may have finished first.
        if (loaded.has(i)) { return; }

        const content = createChunkContent(chunk.heightmap, chunk.features, {
          stdMaterial,
          elevMaterial,
          chunkAspect,
          useElev: settings.elevationOn,
        });
        content.group.position.z = i * chunkZSpan;
        applyLayers(content);
        worldGroup.add(content.group);

        const labels = createChunkLabels(labelsEl, chunk.heightmap, chunk.cities, {
          chunkAspect,
          zOffset: i * chunkZSpan,
        });

        loaded.set(i, { content, labels });
      } catch (err) {
        console.error(`[region-sandbox] failed to load chunk ${i}`, err);
      } finally {
        loading.delete(i);
      }
    }

    /** Ensure the chunks around the camera's z target are loaded (bias south). */
    const stream = () => {
      const idx = Math.min(chunkCount - 1, Math.max(0, Math.round(controls.target.z / chunkZSpan)));
      for (let i = idx - 1; i <= idx + 2; i++) {
        loadChunk(i);
      }
    };

    // Initial load: chunks 0, 1, 2.
    loadChunk(0); loadChunk(1); loadChunk(2);

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
      controls.update();
      stream();
      const w = canvas.clientWidth, h = canvas.clientHeight;
      for (const { labels } of loaded.values()) {
        labels.update(camera, w, h, settings.relief);
      }
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
      setRelief(v) { settings.relief = v; worldGroup.scale.y = v / 0.35; },
      setGlow: ascii.setGlow,
      setCell: ascii.setCell,
      setLight(v) { settings.light = v; key.intensity = v; ambient.intensity = v * 0.156; },
      setRadius: controls.setRadius,
      setTilt: controls.setTilt,
      setOrbitSpeed: controls.setOrbitSpeed,
      setMono: ascii.setMono,
      setElevation(on) {
        settings.elevationOn = on;
        ascii.setElevation(on);
        for (const { content } of loaded.values()) {
          content.terrain.material = on ? elevMaterial : stdMaterial;
        }
      },
      setBandCount(n) { elevMaterial.uniforms.uBands.value = n; },
      setBandCurve(g) { elevMaterial.uniforms.uCurve.value = g; },
      setLayer(name, on) {
        if (!(name in settings.layers)) { return; }
        settings.layers[name] = on;
        for (const { content } of loaded.values()) {
          if (name === 'terrain') {
            content.terrain.visible = on;
          } else {
            const h = content.handles[LAYER_HANDLE[name]];
            if (h) { h.visible = on; }
          }
        }
      },
      getValues() {
        const a = ascii.getValues();
        const c = controls.getValues();
        return {
          relief: settings.relief,
          glow: a.glow,
          cell: a.cell,
          light: settings.light,
          radius: c.radius,
          tilt: c.tiltDeg,
          orbitSpeed: c.orbitSpeed,
          mono: a.mono,
          elevation: settings.elevationOn,
          bandCount: elevMaterial.uniforms.uBands.value,
          bandCurve: elevMaterial.uniforms.uCurve.value,
          'road-major': settings.layers['road-major'],
          'road-sub': settings.layers['road-sub'],
          'water-river': settings.layers['water-river'],
          'water-stream': settings.layers['water-stream'],
          'water-area': settings.layers['water-area'],
          terrain: settings.layers.terrain,
        };
      },
    };
    return api;
  } catch (err) {
    console.error('[region-sandbox] init failed', err);
    showFallback(root);
  }
}
