import * as THREE from 'three';

const DEG = Math.PI / 180;
const TILT_MIN = 5 * DEG;
const TILT_MAX = 25 * DEG;

/**
 * Constrained orbit controls:
 *  - drag horizontal => azimuth orbit; drag vertical => tilt (clamped 5–25°)
 *  - two-finger / wheel-drag => scrub target along fixed map X/Z
 *  - idle => slow auto-orbit (disabled under prefers-reduced-motion)
 */
export function createControls(camera, dom, mapAspect) {
  // mapAspect = height/width: the terrain plane's Z half-extent
  const target = new THREE.Vector3(0, 0, 0);
  const state = { azimuth: 0, tilt: 15 * DEG, radius: 2.6 };
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let lastInteract = 0;

  function apply() {
    const r = state.radius;
    camera.position.set(
      target.x + r * Math.cos(state.tilt) * Math.sin(state.azimuth),
      target.y + r * Math.sin(state.tilt),
      target.z + r * Math.cos(state.tilt) * Math.cos(state.azimuth),
    );
    camera.lookAt(target);
  }

  // --- pointer drag = rotate ---
  let dragging = false, px = 0, py = 0;
  dom.addEventListener('pointerdown', (e) => {
    if (e.pointerType === 'touch') return; // touch handled by two-finger logic below
    dragging = true; px = e.clientX; py = e.clientY; dom.setPointerCapture(e.pointerId);
  });
  dom.addEventListener('pointermove', (e) => {
    if (!dragging) return;
    const dx = e.clientX - px, dy = e.clientY - py;
    px = e.clientX; py = e.clientY;
    state.azimuth -= dx * 0.005;
    state.tilt = Math.min(TILT_MAX, Math.max(TILT_MIN, state.tilt - dy * 0.004));
    lastInteract = performance.now();
  });
  dom.addEventListener('pointerup', () => { dragging = false; });

  // --- two-finger pan / wheel = scrub along fixed map axes ---
  function scrub(dx, dz) {
    const k = 0.0015;
    target.x = Math.min(1, Math.max(-1, target.x + dx * k));
    target.z = Math.min(mapAspect, Math.max(-mapAspect, target.z + dz * k));
    lastInteract = performance.now();
  }
  dom.addEventListener('wheel', (e) => { e.preventDefault(); scrub(e.deltaX, e.deltaY); }, { passive: false });

  let touches = [];
  dom.addEventListener('touchstart', (e) => { touches = [...e.touches]; }, { passive: true });
  dom.addEventListener('touchmove', (e) => {
    if (e.touches.length >= 1) e.preventDefault();
    if (e.touches.length === 2 && touches.length === 2) {
      const dx = e.touches[0].clientX - touches[0].clientX;
      const dy = e.touches[0].clientY - touches[0].clientY;
      scrub(-dx, -dy);
    } else if (e.touches.length === 1 && touches.length === 1) {
      const dx = e.touches[0].clientX - touches[0].clientX;
      const dy = e.touches[0].clientY - touches[0].clientY;
      state.azimuth -= dx * 0.005;
      state.tilt = Math.min(TILT_MAX, Math.max(TILT_MIN, state.tilt - dy * 0.004));
      lastInteract = performance.now();
    }
    touches = [...e.touches];
  }, { passive: false });

  return function updateControls() {
    if (!reduceMotion && performance.now() - lastInteract > 2500) {
      state.azimuth += 0.0015; // slow auto-orbit
    }
    apply();
  };
}
