import * as THREE from 'three';
import { pathPointAt, pathLength } from './projection.js';

const DEG = Math.PI / 180;
const TILT_MIN = 15 * DEG;
const TILT_MAX = 45 * DEG;

/**
 * Constrained orbit controls:
 *  - drag horizontal => azimuth orbit; drag vertical => tilt (clamped 15–45°)
 *  - two-finger / wheel-drag => scrub target along fixed map X/Z
 *  - idle => slow auto-orbit (disabled under prefers-reduced-motion)
 */
export function createControls(camera, dom) {
  const target = new THREE.Vector3(0, 0, 0);
  const state = { azimuth: 0, tilt: 45 * DEG, radius: 1.7 };
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let lastInteract = 0;
  let orbitSpeed = 0.001;
  // Pan is a 1-D arc-length `s` along a world polyline (the corridor path).
  let pathPts = [{ x: 0, z: 0 }];
  let pathLen = 0;
  let s = 0;

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

  // --- two-finger pan / wheel = advance along the path ---
  function setTargetFromS() {
    const p = pathPointAt(s, pathPts);
    target.x = p.x;
    target.z = p.z;
  }
  function scrub(dx, dz) {
    const k = 0.0015;
    s = Math.min(pathLen, Math.max(0, s + dz * k)); // vertical delta advances the path
    setTargetFromS();
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

  const update = function updateControls() {
    if (!reduceMotion && performance.now() - lastInteract > 2500) {
      state.azimuth += orbitSpeed; // slow auto-orbit
    }
    apply();
  };

  // Tuning setters use a widened clamp (5..80°) so a slider has room; pointer
  // drag keeps the original TILT_MIN/TILT_MAX (15..45°) clamp.
  const TUNE_TILT_MIN = 5 * DEG;
  const TUNE_TILT_MAX = 80 * DEG;
  const setRadius = (v) => { state.radius = v; };
  const setTilt = (deg) => { state.tilt = Math.min(TUNE_TILT_MAX, Math.max(TUNE_TILT_MIN, deg * DEG)); };
  const setOrbitSpeed = (v) => { orbitSpeed = v; };
  const setPath = (points) => {
    pathPts = points.length ? points : [{ x: 0, z: 0 }];
    pathLen = pathLength(pathPts);
    s = Math.min(pathLen, Math.max(0, s));
    setTargetFromS();
  };
  const setS = (v) => { s = Math.min(pathLen, Math.max(0, v)); setTargetFromS(); };
  const getS = () => s;
  const getValues = () => ({ radius: state.radius, tiltDeg: state.tilt / DEG, orbitSpeed });

  return { update, setRadius, setTilt, setOrbitSpeed, setPath, setS, getS, target, getValues };
}
