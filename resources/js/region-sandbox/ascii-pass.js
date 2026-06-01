import * as THREE from 'three';

const GLYPHS = ' .:-=+*#%@'; // sparse -> dense
const CELL = 3; // screen px per glyph cell (smaller = higher ASCII resolution)
const ATLAS_CELL = 24; // px per glyph in the atlas texture (kept high for crisp glyphs)

/** Renders GLYPHS into a single-row atlas texture (N cells wide). */
function makeAtlas() {
  const n = GLYPHS.length;
  const c = document.createElement('canvas');
  c.width = ATLAS_CELL * n; c.height = ATLAS_CELL;
  const ctx = c.getContext('2d');
  ctx.fillStyle = '#000'; ctx.fillRect(0, 0, c.width, c.height);
  ctx.fillStyle = '#fff';
  // Oversize the font so glyph ink fills the cell height — at 1:1 the ink only covers
  // the middle of the cell, leaving black strips top/bottom that read as gaps between
  // rows ("rings of nothingness") on solid vertical features.
  ctx.font = `${Math.round(ATLAS_CELL * 1.3)}px monospace`;
  ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
  for (let i = 0; i < n; i++) ctx.fillText(GLYPHS[i], i * ATLAS_CELL + ATLAS_CELL / 2, ATLAS_CELL / 2);
  const tex = new THREE.CanvasTexture(c);
  tex.minFilter = THREE.NearestFilter; tex.magFilter = THREE.NearestFilter;
  return { tex, count: n };
}

/**
 * Full-screen ASCII post-process. Renders `scene/camera` to a target, then
 * maps per-cell luminance to a phosphor glyph.
 */
export function createAsciiPass(renderer, scene, camera) {
  const size = new THREE.Vector2();
  renderer.getSize(size);
  const target = new THREE.WebGLRenderTarget(size.x, size.y);
  const { tex, count } = makeAtlas();

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
      uMinLum: { value: 0.06 }, // below this -> blank (background); above -> at least the faintest glyph
    },
    vertexShader: `varying vec2 vUv; void main(){ vUv = uv; gl_Position = vec4(position.xy, 0.0, 1.0); }`,
    fragmentShader: `
      precision highp float;
      varying vec2 vUv;
      uniform sampler2D uScene, uAtlas;
      uniform vec2 uResolution;
      uniform float uCell, uCount, uGlow, uMono, uElevation, uMinLum;
      uniform vec3 uPhosphor;
      float lum(vec3 c){ return dot(c, vec3(0.299,0.587,0.114)); }
      void main(){
        vec2 px = vUv * uResolution;
        vec2 cellOrigin = floor(px / uCell) * uCell;
        // max luminance over a 3x3 grid in the cell, so a thin feature line
        // anywhere inside it still lights the glyph (no rotate-and-vanish dropout)
        float l = 0.0;
        vec3 maxColor = vec3(0.0);
        for (int i = 0; i < 3; i++) {
          for (int j = 0; j < 3; j++) {
            vec2 frac = (vec2(float(i), float(j)) + 0.5) / 3.0;
            vec2 suv = (cellOrigin + frac * uCell) / uResolution;
            vec3 tap = texture2D(uScene, suv).rgb;
            float tl = lum(tap);
            if (tl > l) { l = tl; maxColor = tap; }
          }
        }
        float lv = clamp(l, 0.0, 0.999);
        float gi = floor(lv * uCount);                       // glyph index
        if (gi < 1.0 && lv > uMinLum) { gi = 1.0; }          // dim-but-present -> faintest glyph, not blank
        vec2 local = (px - cellOrigin) / uCell;               // 0..1 within cell
        vec2 atlasUv = vec2((gi + local.x) / uCount, 1.0 - local.y);
        float glyph = texture2D(uAtlas, atlasUv).r;
        bool useColor = (uMono < 0.5) || (uElevation > 0.5);
        vec3 base = useColor ? maxColor : uPhosphor;
        vec3 col = base * glyph * (1.0 + uGlow);
        gl_FragColor = vec4(col, 1.0);
      }`,
  });
  const quad = new THREE.Mesh(new THREE.PlaneGeometry(2, 2), mat);
  quadScene.add(quad);

  function resize(w, h) {
    target.setSize(w, h);
    mat.uniforms.uResolution.value.set(w, h);
  }

  function render() {
    renderer.setRenderTarget(target);
    renderer.render(scene, camera);
    renderer.setRenderTarget(null);
    renderer.render(quadScene, quadCam);
  }

  const setGlow = (v) => { mat.uniforms.uGlow.value = v; };
  const setCell = (v) => { mat.uniforms.uCell.value = v; };
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
