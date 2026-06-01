import * as THREE from 'three';
import { sampleHeight, RELIEF } from './scene.js';
import { recencyDays, recencyToSize, countdownLabel } from './events.js';

const SPARKLE_MAX = 24; // particles per sparkling beacon

/** Escape admin-entered text before innerHTML interpolation (text + double-quoted attrs). */
function escapeHtml(s) {
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
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

    // Beacon column: unit-height box scaled per-frame by the size envelope.
    const beaconGeo = new THREE.BoxGeometry(1, 1, 1);
    const beaconMat = new THREE.MeshBasicMaterial({ color: color.clone(), transparent: true });
    const beacon = new THREE.Mesh(beaconGeo, beaconMat);
    group.add(beacon);

    // Sparkle emitter (created lazily-ish: geometry always present, draw range gates it).
    const sparkPos = new Float32Array(SPARKLE_MAX * 3);
    const sparkSeed = new Float32Array(SPARKLE_MAX); // per-particle phase 0..1
    for (let i = 0; i < SPARKLE_MAX; i++) {
      sparkSeed[i] = i / SPARKLE_MAX;
    }
    const sparkGeo = new THREE.BufferGeometry();
    sparkGeo.setAttribute('position', new THREE.BufferAttribute(sparkPos, 3));
    const sparkMat = new THREE.PointsMaterial({
      color: color.clone(), size: 0.035, transparent: true, depthWrite: false,
      blending: THREE.AdditiveBlending,
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
      g, x, z, baseY, beacon, beaconMat, sparkles, sparkGeo, sparkMat, sparkPos, sparkSeed,
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

      // Sparkle: only within the sparkle horizon, intensity ramps toward the event.
      const sparkDays = thresholds.sparkleHorizon;
      const sparkOn = !reduceMotion && days <= sparkDays;
      it.sparkles.visible = sparkOn;
      if (sparkOn) {
        const intensity = (1 - Math.min(1, Math.max(0, days) / sparkDays)) * thresholds.sparkleIntensity;
        const count = Math.max(1, Math.round(SPARKLE_MAX * intensity));
        it.sparkGeo.setDrawRange(0, count);
        it.sparkMat.opacity = 0.35 + 0.65 * intensity;
        it.sparkMat.size = 0.025 + 0.03 * intensity;
        it.sparkles.position.set(it.x, baseY, it.z);

        const tSec = nowMs / 1000;
        const rise = size.height * 1.15;
        for (let i = 0; i < count; i++) {
          const phase = (tSec * 0.6 + it.sparkSeed[i]) % 1;
          const a = it.sparkSeed[i] * 6.2831853;
          const r = size.width * (1.5 + 2.5 * phase);
          it.sparkPos[i * 3] = Math.cos(a + tSec) * r;
          it.sparkPos[i * 3 + 1] = phase * rise;
          it.sparkPos[i * 3 + 2] = Math.sin(a + tSec) * r;
        }
        it.sparkGeo.attributes.position.needsUpdate = true;
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
