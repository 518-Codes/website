import * as THREE from 'three';

const GLYPHS = ' .:-=+*#%@'; // sparse -> dense
const CELL = 8; // px per glyph cell

/** Renders GLYPHS into a single-row atlas texture (N cells wide). */
function makeAtlas() {
  const n = GLYPHS.length;
  const c = document.createElement('canvas');
  c.width = CELL * n; c.height = CELL;
  const ctx = c.getContext('2d');
  ctx.fillStyle = '#000'; ctx.fillRect(0, 0, c.width, c.height);
  ctx.fillStyle = '#fff';
  ctx.font = `${CELL}px monospace`;
  ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
  for (let i = 0; i < n; i++) ctx.fillText(GLYPHS[i], i * CELL + CELL / 2, CELL / 2);
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
    },
    vertexShader: `varying vec2 vUv; void main(){ vUv = uv; gl_Position = vec4(position.xy, 0.0, 1.0); }`,
    fragmentShader: `
      precision highp float;
      varying vec2 vUv;
      uniform sampler2D uScene, uAtlas;
      uniform vec2 uResolution;
      uniform float uCell, uCount, uGlow;
      uniform vec3 uPhosphor;
      float lum(vec3 c){ return dot(c, vec3(0.299,0.587,0.114)); }
      void main(){
        vec2 px = vUv * uResolution;
        vec2 cellOrigin = floor(px / uCell) * uCell;
        vec2 cellCenterUv = (cellOrigin + uCell*0.5) / uResolution;
        float l = lum(texture2D(uScene, cellCenterUv).rgb);
        float gi = floor(clamp(l, 0.0, 0.999) * uCount);     // glyph index
        vec2 local = (px - cellOrigin) / uCell;               // 0..1 within cell
        vec2 atlasUv = vec2((gi + local.x) / uCount, 1.0 - local.y);
        float glyph = texture2D(uAtlas, atlasUv).r;
        vec3 col = uPhosphor * glyph * (1.0 + uGlow);
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

  return { render, resize };
}
