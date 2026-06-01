// resources/js/region-sandbox/fireworks.js
import * as THREE from 'three';

const PARTICLES = 90;   // per burst
const LIFE = 1.5;       // seconds
const GRAVITY = 1.2;    // world units / s^2 (scene is ~unit scale)

/** Ballistic position of a particle at age `t` (seconds). Pure. */
export function fireworkParticleAt(p0, vel, gravity, t) {
  return {
    x: p0.x + vel.x * t,
    y: p0.y + vel.y * t - 0.5 * gravity * t * t,
    z: p0.z + vel.z * t,
  };
}

/**
 * Firework manager: spawn() launches a one-shot additive burst at a world point;
 * update(dt) advances active bursts and removes spent ones. Under reduceMotion the
 * burst is a brief static bloom (no flight).
 *
 * @param {THREE.Group} parent group to add bursts into (worldGroup)
 * @param {{ reduceMotion:boolean }} opts
 */
export function createFireworks(parent, { reduceMotion }) {
  const bursts = [];

  const spawn = (worldPos, hue = 0.12) => {
    const pos = new Float32Array(PARTICLES * 3);
    const col = new Float32Array(PARTICLES * 3);
    const vel = [];
    const base = new THREE.Color();
    for (let i = 0; i < PARTICLES; i++) {
      // random direction on a hemisphere, biased upward
      const az = Math.random() * Math.PI * 2;
      const el = (0.15 + Math.random() * 0.85) * (Math.PI / 2);
      const speed = 0.6 + Math.random() * 0.9;
      const ce = Math.cos(el);
      vel.push({ x: Math.cos(az) * ce * speed, y: Math.sin(el) * speed, z: Math.sin(az) * ce * speed });
      pos[i * 3] = worldPos.x; pos[i * 3 + 1] = worldPos.y; pos[i * 3 + 2] = worldPos.z;
      base.setHSL((hue + Math.random() * 0.2) % 1, 1, 0.6);
      col[i * 3] = base.r; col[i * 3 + 1] = base.g; col[i * 3 + 2] = base.b;
    }
    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
    geo.setAttribute('color', new THREE.BufferAttribute(col, 3));
    const mat = new THREE.PointsMaterial({ size: 0.02, transparent: true, depthWrite: false, vertexColors: true, blending: THREE.AdditiveBlending });
    const points = new THREE.Points(geo, mat);
    parent.add(points);
    bursts.push({ points, geo, mat, pos, vel, p0: { ...worldPos }, age: 0 });
  };

  const update = (dt) => {
    for (let b = bursts.length - 1; b >= 0; b--) {
      const burst = bursts[b];
      burst.age += dt;
      const life = reduceMotion ? LIFE * 0.6 : LIFE;
      const frac = burst.age / life;
      if (frac >= 1) {
        parent.remove(burst.points); burst.geo.dispose(); burst.mat.dispose();
        bursts.splice(b, 1);
        continue;
      }
      burst.mat.opacity = 1 - frac; // fade out
      if (reduceMotion) { continue; } // static bloom: no flight
      const t = burst.age;
      for (let i = 0; i < PARTICLES; i++) {
        const p = fireworkParticleAt(burst.p0, burst.vel[i], GRAVITY, t);
        burst.pos[i * 3] = p.x; burst.pos[i * 3 + 1] = p.y; burst.pos[i * 3 + 2] = p.z;
      }
      burst.geo.attributes.position.needsUpdate = true;
    }
  };

  const dispose = () => {
    for (const burst of bursts) { parent.remove(burst.points); burst.geo.dispose(); burst.mat.dispose(); }
    bursts.length = 0;
  };

  return { spawn, update, dispose };
}
