import * as THREE from 'three';

const GLYPHS = ' .:-=+*#%@'; // sparse -> dense
const CELL = 5; // screen px per glyph cell (smaller = higher ASCII resolution)
const ATLAS_CELL = 16; // px per glyph in the atlas texture (kept high for crisp glyphs)

/** Renders GLYPHS into a single-row atlas texture (N cells wide). */
function makeAtlas() {
  const n = GLYPHS.length;
  const c = document.createElement('canvas');
  c.width = ATLAS_CELL * n; c.height = ATLAS_CELL;
  const ctx = c.getContext('2d');
  ctx.fillStyle = '#000'; ctx.fillRect(0, 0, c.width, c.height);
  ctx.fillStyle = '#fff';
  ctx.font = `${ATLAS_CELL}px monospace`;
  ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
  for (let i = 0; i < n; i++) ctx.fillText(GLYPHS[i], i * ATLAS_CELL + ATLAS_CELL / 2, ATLAS_CELL / 2);
  const tex = new THREE.CanvasTexture(c);
  tex.minFilter = THREE.NearestFilter; tex.magFilter = THREE.NearestFilter;
  return { tex, count: n };
}

/**
 * Full-screen ASCII post-process. The scene is rendered into an offscreen target
 * sized to the GLYPH-CELL GRID (one texel per cell), so the GPU rasterizes every
 * feature into whichever cells it crosses — no phase-dependent sampling dropout
 * (which caused features to vanish at certain cell sizes). The full-screen quad
 * then stamps a glyph per cell, colored by that cell's single texel.
 */
export function createAsciiPass(renderer, scene, camera) {
  const size = new THREE.Vector2();
  renderer.getSize(size);
  const target = new THREE.WebGLRenderTarget(size.x, size.y);
  // Nearest + no mipmaps: each cell maps to exactly one texel, no bleeding.
  target.texture.minFilter = THREE.NearestFilter;
  target.texture.magFilter = THREE.NearestFilter;
  target.texture.generateMipmaps = false;
  const { tex, count } = makeAtlas();

  // Viewport size in CSS px (the cell grid is derived from this and uCell).
  let vw = size.x;
  let vh = size.y;

  const quadScene = new THREE.Scene();
  const quadCam = new THREE.OrthographicCamera(-1, 1, 1, -1, 0, 1);
  const mat = new THREE.ShaderMaterial({
    uniforms: {
      uScene: { value: target.texture },
      uAtlas: { value: tex },
      uResolution: { value: new THREE.Vector2(size.x, size.y) },
      uCell: { value: CELL },
      uCount: { value: count },
      uPhosphor: { value: new THREE.Color(0x5efc8d) },
      uGlow: { value: 0.25 },
      uMono: { value: 0.0 },
      uElevation: { value: 0.0 },
    },
    vertexShader: `varying vec2 vUv; void main(){ vUv = uv; gl_Position = vec4(position.xy, 0.0, 1.0); }`,
    fragmentShader: `
      precision highp float;
      varying vec2 vUv;
      uniform sampler2D uScene, uAtlas;
      uniform vec2 uResolution;
      uniform float uCell, uCount, uGlow, uMono, uElevation;
      uniform vec3 uPhosphor;
      float lum(vec3 c){ return dot(c, vec3(0.299,0.587,0.114)); }
      void main(){
        vec2 px = vUv * uResolution;
        vec2 cellOrigin = floor(px / uCell) * uCell;
        // The scene target is rendered at cell-grid resolution, so one texel == one
        // cell. Sampling at the cell center (nearest) reads that cell's feature/terrain.
        vec2 cellCenterUv = (cellOrigin + uCell * 0.5) / uResolution;
        vec3 tap = texture2D(uScene, cellCenterUv).rgb;
        float l = lum(tap);
        float gi = floor(clamp(l, 0.0, 0.999) * uCount);     // glyph index
        vec2 local = (px - cellOrigin) / uCell;               // 0..1 within cell
        vec2 atlasUv = vec2((gi + local.x) / uCount, 1.0 - local.y);
        float glyph = texture2D(uAtlas, atlasUv).r;
        bool useColor = (uMono < 0.5) || (uElevation > 0.5);
        vec3 base = useColor ? tap : uPhosphor;
        vec3 col = base * glyph * (1.0 + uGlow);
        gl_FragColor = vec4(col, 1.0);
      }`,
  });
  const quad = new THREE.Mesh(new THREE.PlaneGeometry(2, 2), mat);
  quadScene.add(quad);

  /** Size the render target to the current cell grid (cols x rows). */
  function applyTargetSize() {
    const cell = mat.uniforms.uCell.value;
    const cols = Math.max(1, Math.round(vw / cell));
    const rows = Math.max(1, Math.round(vh / cell));
    target.setSize(cols, rows);
  }

  function resize(w, h) {
    vw = w;
    vh = h;
    mat.uniforms.uResolution.value.set(w, h);
    applyTargetSize();
  }

  function render() {
    renderer.setRenderTarget(target);
    renderer.render(scene, camera);
    renderer.setRenderTarget(null);
    renderer.render(quadScene, quadCam);
  }

  const setGlow = (v) => { mat.uniforms.uGlow.value = v; };
  const setCell = (v) => { mat.uniforms.uCell.value = v; applyTargetSize(); };
  const setMono = (b) => { mat.uniforms.uMono.value = b ? 1.0 : 0.0; };
  const setElevation = (b) => { mat.uniforms.uElevation.value = b ? 1.0 : 0.0; };
  const getValues = () => ({
    glow: mat.uniforms.uGlow.value,
    cell: mat.uniforms.uCell.value,
    mono: mat.uniforms.uMono.value > 0.5,
    elevation: mat.uniforms.uElevation.value > 0.5,
  });

  return { render, resize, setGlow, setCell, setMono, setElevation, getValues };
}
