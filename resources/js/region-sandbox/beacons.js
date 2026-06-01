import * as THREE from 'three';
import { sampleHeight, RELIEF } from './scene.js';
import { recencyDays, recencyToSize, countdownLabel } from './events.js';

const SPARKLE_MAX = 40; // particle pool per sparkling beacon (volume slider gates how many draw)

/** Escape admin-entered text before innerHTML interpolation (text + double-quoted attrs). */
function escapeHtml(s) {
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

/** Vertex-color gradient on a unit cone: bright at the base, dim toward the apex. */
function applyVerticalGradient(geo) {
  const pos = geo.attributes.position;
  const colors = new Float32Array(pos.count * 3);
  for (let i = 0; i < pos.count; i++) {
    const t = pos.getY(i) + 0.5;   // 0 at base, 1 at apex (unit cone spans -0.5..+0.5)
    const b = 1.0 - 0.95 * t;      // 1.0 at base -> 0.05 at apex (strong, visible falloff)
    colors[i * 3] = b;
    colors[i * 3 + 1] = b;
    colors[i * 3 + 2] = b;
  }
  geo.setAttribute('color', new THREE.BufferAttribute(colors, 3));
}

/**
 * Build beacon meshes + sparkle emitters + HTML labels for one chunk's event groups.
 * Mirrors createChunkLabels: world Z accounts for the chunk's zOffset; heights use
 * the chunk heightmap so beacons sit on terrain.
 *
 * @param {HTMLElement} labelsEl the `.region-labels` overlay
 * @param {{width:number,height:number,data:number[]}} heightmap
 * @param {Array} groups output of groupByLocation, each with chunk-local x,z in [0,1]
 * @param {{chunkAspect:number, zOffset:number, thresholds:object, getNowMs:()=>number, reduceMotion:boolean}} opts
 * @returns {{ group: THREE.Group, items: Array, update: Function, dispose: Function }}
 */
export function createChunkBeacons(labelsEl, heightmap, groups, opts) {
  const { chunkAspect, zOffset, thresholds, getNowMs, reduceMotion } = opts;
  const group = new THREE.Group();
  const color = new THREE.Color(0xffd27f); // warm "loot" tone; pops against phosphor green

  const items = groups.map((g) => {
    const x = g.x * 2 - 1;
    const z = (g.z * 2 - 1) * chunkAspect + zOffset;
    const baseY = sampleHeight(heightmap, g.x, g.z) * RELIEF;

    // Beacon: a slender unit cone (wide base -> point top, an inverted-funnel spire),
    // scaled per-frame by the size envelope. A vertex-color gradient makes it brighter
    // at the base and dim toward the top; per-frame glow tints the whole cone.
    const beaconGeo = new THREE.ConeGeometry(1, 1, 14, 6);
    applyVerticalGradient(beaconGeo);
    const beaconMat = new THREE.MeshBasicMaterial({ color: color.clone(), transparent: true, vertexColors: true });
    const beacon = new THREE.Mesh(beaconGeo, beaconMat);
    group.add(beacon);

    // Sparkle emitter: a constant stream of fine glints rising from the base centre
    // outward into a short, randomized dome. Per-particle direction + phase offset are
    // set once so emission is a steady stream (not synchronized bursts); per-particle
    // colour (additive) does the fade in/out.
    const sparkPos = new Float32Array(SPARKLE_MAX * 3);
    const sparkCol = new Float32Array(SPARKLE_MAX * 3);
    const sparkAz = new Float32Array(SPARKLE_MAX);   // azimuth around the base
    const sparkEl = new Float32Array(SPARKLE_MAX);   // dome elevation (0 = flat .. up)
    const sparkOff = new Float32Array(SPARKLE_MAX);  // phase offset -> staggered stream
    const sparkSpd = new Float32Array(SPARKLE_MAX);  // per-particle speed
    const sparkRad = new Float32Array(SPARKLE_MAX);  // per-particle reach scale
    for (let i = 0; i < SPARKLE_MAX; i++) {
      sparkAz[i] = Math.random() * 6.2831853;
      sparkEl[i] = (0.45 + Math.random() * 0.55) * (Math.PI / 2); // 40°..90°: always upward
      sparkOff[i] = Math.random();
      sparkSpd[i] = 0.7 + Math.random() * 0.7;
      sparkRad[i] = 0.6 + Math.random() * 0.8;
    }
    const sparkGeo = new THREE.BufferGeometry();
    sparkGeo.setAttribute('position', new THREE.BufferAttribute(sparkPos, 3));
    sparkGeo.setAttribute('color', new THREE.BufferAttribute(sparkCol, 3));
    const sparkMat = new THREE.PointsMaterial({
      size: 0.016, transparent: true, depthWrite: false,
      vertexColors: true, blending: THREE.AdditiveBlending,
    });
    const sparkles = new THREE.Points(sparkGeo, sparkMat);
    sparkles.position.set(x, baseY, z);
    group.add(sparkles);

    // HTML label (identity + countdown + "(+N more)"), clickable for drill-in.
    const el = document.createElement('div');
    el.className = 'region-event-label';
    labelsEl.appendChild(el);

    const popover = document.createElement('div');
    popover.className = 'region-event-popover';
    labelsEl.appendChild(popover);
    el.addEventListener('click', (e) => {
      e.stopPropagation();
      if (g.events.length === 1) {
        window.open(g.events[0].url, g.events[0].url.startsWith(window.location.origin) ? '_self' : '_blank');
        return;
      }
      popover.classList.toggle('open');
    });

    return {
      g, x, z, baseY, beacon, beaconMat, sparkles, sparkGeo, sparkMat,
      sparkPos, sparkCol, sparkAz, sparkEl, sparkOff, sparkSpd, sparkRad,
      el, popover, world: new THREE.Vector3(x, baseY, z),
    };
  });

  const v = new THREE.Vector3();

  const update = function update(camera, w, h, relief) {
    const nowMs = getNowMs();
    for (const it of items) {
      const days = recencyDays(it.g.soonest.startsAtMs, nowMs);
      const size = recencyToSize(days, thresholds);

      if (!size.visible) {
        it.beacon.visible = false;
        it.sparkles.visible = false;
        it.el.style.display = 'none';
        it.popover.classList.remove('open');
        continue;
      }

      const yScale = relief / RELIEF;
      const baseY = it.baseY * yScale;

      // Beacon: stand the unit box up to `height`, centered above the ground point.
      it.beacon.visible = true;
      it.beacon.scale.set(size.width, size.height, size.width);
      it.beacon.position.set(it.x, baseY + size.height / 2, it.z);
      it.beaconMat.opacity = Math.min(1, 0.55 + 0.45 * size.glow);
      it.beacon.material.color.copy(color).multiplyScalar(Math.min(1, 0.6 + size.glow));

      // Sparkle: a subtle constant stream rising from the base centre outward into a
      // short, randomized dome. Only within the sparkle horizon; density + brightness
      // ramp in as the event nears.
      const sparkDays = thresholds.sparkleHorizon;
      const sparkOn = !reduceMotion && days <= sparkDays;
      it.sparkles.visible = sparkOn;
      if (sparkOn) {
        const ramp = 1 - Math.min(1, Math.max(0, days) / sparkDays); // 0 far .. 1 near
        const count = Math.max(1, Math.round(SPARKLE_MAX * thresholds.sparkleVolume));
        it.sparkGeo.setDrawRange(0, count);
        it.sparkMat.size = thresholds.sparkleSize;
        it.sparkles.position.set(it.x, baseY, it.z);

        const tSec = nowMs / 1000;
        const domeR = 0.05 + size.width * 3;       // short, subtle footprint
        const domeH = domeR * 1.3;                 // taller than wide -> upward fountain
        const peak = thresholds.sparkleIntensity * (0.35 + 0.65 * ramp); // brightness, ramps w/ recency
        for (let i = 0; i < count; i++) {
          const phase = (tSec * 0.35 * thresholds.sparkleSpeed * it.sparkSpd[i] + it.sparkOff[i]) % 1;
          const reach = phase * it.sparkRad[i];
          const ce = Math.cos(it.sparkEl[i]);
          it.sparkPos[i * 3] = Math.cos(it.sparkAz[i]) * ce * domeR * reach;
          it.sparkPos[i * 3 + 1] = Math.sin(it.sparkEl[i]) * domeH * reach;
          it.sparkPos[i * 3 + 2] = Math.sin(it.sparkAz[i]) * ce * domeR * reach;
          const a = Math.sin(phase * Math.PI) * peak; // fade in then out over each life
          it.sparkCol[i * 3] = color.r * a;
          it.sparkCol[i * 3 + 1] = color.g * a;
          it.sparkCol[i * 3 + 2] = color.b * a;
        }
        it.sparkGeo.attributes.position.needsUpdate = true;
        it.sparkGeo.attributes.color.needsUpdate = true;
      }

      // Label (+ popover) — only within the label horizon.
      const labeled = days <= thresholds.labelHorizon;
      it.el.style.display = labeled ? 'block' : 'none';
      if (labeled) {
        const more = it.g.events.length - 1;
        const moreTxt = more > 0 ? ` <span class="evt-more">(+${more} more)</span>` : '';
        it.el.innerHTML = `${escapeHtml(it.g.soonest.title)}<span class="evt-when">${countdownLabel(days)}</span>${moreTxt}`;
        if (more > 0 && !it.popover.dataset.built) {
          it.popover.innerHTML = it.g.events
            .map((e) => `<a href="${escapeHtml(e.url)}">${escapeHtml(e.title)} — ${countdownLabel(recencyDays(e.startsAtMs, nowMs))}</a>`)
            .join('');
          it.popover.dataset.built = '1';
        }

        it.world.set(it.x, baseY + size.height + 0.05, it.z);
        v.copy(it.world).project(camera);
        const behind = v.z > 1;
        const left = (v.x * 0.5 + 0.5) * w;
        const top = (-v.y * 0.5 + 0.5) * h;
        it.el.style.display = behind ? 'none' : 'block';
        it.el.style.left = `${left}px`;
        it.el.style.top = `${top}px`;
        it.popover.style.left = `${left}px`;
        it.popover.style.top = `${top + 14}px`;
        if (behind) {
          it.popover.classList.remove('open');
        }
      } else {
        it.popover.classList.remove('open');
      }
    }
  };

  const dispose = () => {
    for (const it of items) {
      it.beacon.geometry.dispose();
      it.beaconMat.dispose();
      it.sparkGeo.dispose();
      it.sparkMat.dispose();
      if (it.el.parentNode) { it.el.parentNode.removeChild(it.el); }
      if (it.popover.parentNode) { it.popover.parentNode.removeChild(it.popover); }
    }
  };

  return { group, items, update, dispose };
}
