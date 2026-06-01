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

// Beacon gradient lives in the fragment shader (computed from local height) rather than
// vertex colors, which did not render reliably through the ASCII post-process pass.
const BEACON_VERT = `
  varying float vT;
  void main(){
    vT = position.y + 0.5;                 // 0 at base, 1 at apex (unit cone spans -0.5..0.5)
    gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
  }`;
const BEACON_FRAG = `
  precision highp float;
  uniform vec3 uColor;
  uniform float uGlow, uOpacity, uGradient;
  varying float vT;
  void main(){
    float b = 1.0 - uGradient * vT;        // base 1.0 -> top (1.0 - uGradient); 0 = flat
    gl_FragColor = vec4(uColor * b * uGlow, uOpacity);
  }`;

/**
 * Build beacon meshes + sparkle emitters + HTML labels for one chunk's event groups.
 * Mirrors createChunkLabels: world placement via the chunk world rect; heights use
 * the chunk heightmap so beacons sit on terrain.
 *
 * @param {HTMLElement} labelsEl the `.region-labels` overlay
 * @param {{width:number,height:number,data:number[]}} heightmap
 * @param {Array} groups output of groupByLocation, each with chunk-local x,z in [0,1]
 * @param {{ world:{x:number,z:number,w:number,d:number}, thresholds:object, getNowMs:()=>number, reduceMotion:boolean }} opts
 * @returns {{ group: THREE.Group, items: Array, update: Function, dispose: Function }}
 */
export function createChunkBeacons(labelsEl, heightmap, groups, opts) {
  const { world, thresholds, getNowMs, reduceMotion } = opts;
  const group = new THREE.Group();
  // Beacon + sparkle tint from a tunable hue + saturation at a fixed loot-glow lightness.
  const color = new THREE.Color().setHSL(thresholds.beaconHue / 360, thresholds.beaconSaturation, 0.75);

  // Two beacon shapes shared across this chunk's beacons, swapped live by `beaconCone`.
  // The truncated cone keeps a flat top so the dim gradient registers through the ASCII
  // cells (a sharp apex is a sub-cell point); the box is the alternative square shape.
  const coneGeo = new THREE.CylinderGeometry(0.3, 1, 1, 16, 6);
  const boxGeo = new THREE.BoxGeometry(1, 1, 1);
  // Shared invisible hover hit-volume (unit cylinder, scaled per beacon) + its raycast list.
  const hitGeo = new THREE.CylinderGeometry(1, 1, 1, 8);
  const hitMat = new THREE.MeshBasicMaterial({ visible: false });
  const hitMeshes = [];

  const items = groups.map((g) => {
    const x = world.x + (g.x - 0.5) * world.w;
    const z = world.z + (g.z - 0.5) * world.d;
    const baseY = sampleHeight(heightmap, g.x, g.z) * RELIEF;

    // Beacon scaled per-frame by the size envelope; shape (cone/box) swapped per-frame.
    // Shader fades bright-at-base to dim-at-top; uGlow/uOpacity/uColor set per-frame.
    const beaconMat = new THREE.ShaderMaterial({
      uniforms: {
        uColor: { value: color.clone() },
        uGlow: { value: 1.0 },
        uOpacity: { value: 1.0 },
        uGradient: { value: thresholds.beaconGradient },
      },
      transparent: true,
      vertexShader: BEACON_VERT,
      fragmentShader: BEACON_FRAG,
    });
    const beacon = new THREE.Mesh(thresholds.beaconCone ? coneGeo : boxGeo, beaconMat);
    group.add(beacon);

    // Invisible, generous hover hit-volume so even small/far beacons are hoverable.
    const hit = new THREE.Mesh(hitGeo, hitMat);
    hit.userData.beaconKey = g.key;
    group.add(hit);
    hitMeshes.push(hit);

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
      g, x, z, baseY, beacon, beaconMat, hit, sparkles, sparkGeo, sparkMat,
      sparkPos, sparkCol, sparkAz, sparkEl, sparkOff, sparkSpd, sparkRad,
      el, popover, world: new THREE.Vector3(x, baseY, z),
    };
  });

  const v = new THREE.Vector3();

  const update = function update(camera, w, h, relief, hoveredKey) {
    const nowMs = getNowMs();
    color.setHSL(thresholds.beaconHue / 360, thresholds.beaconSaturation, 0.75); // live hue+sat
    // CSS forms of the live beacon color so the HTML labels match their beacon.
    const cr = Math.round(color.r * 255), cg = Math.round(color.g * 255), cb = Math.round(color.b * 255);
    const labelColor = `rgb(${cr}, ${cg}, ${cb})`;
    const labelGlow = `rgba(${cr}, ${cg}, ${cb}, 0.7)`;
    for (const it of items) {
      const days = recencyDays(it.g.soonest.startsAtMs, nowMs);
      const size = recencyToSize(days, thresholds);

      if (!size.visible) {
        it.beacon.visible = false;
        it.hit.visible = false;
        it.sparkles.visible = false;
        it.el.style.display = 'none';
        it.popover.classList.remove('open');
        continue;
      }
      const hovered = hoveredKey != null && hoveredKey === it.g.key;

      const yScale = relief / RELIEF;
      const baseY = it.baseY * yScale;

      // Beacon: stand the unit cone up to `height`, base on the ground point. The
      // shader handles the base->top gradient; uGlow scales brightness per recency.
      it.beacon.visible = true;
      it.beacon.scale.set(size.width, size.height, size.width);
      it.beacon.position.set(it.x, baseY + size.height / 2, it.z);
      it.beacon.geometry = thresholds.beaconCone ? coneGeo : boxGeo;
      it.beaconMat.uniforms.uColor.value.copy(color);
      it.beaconMat.uniforms.uGlow.value = Math.min(1.5, 0.6 + size.glow);
      it.beaconMat.uniforms.uOpacity.value = Math.min(1, 0.55 + 0.45 * size.glow);
      it.beaconMat.uniforms.uGradient.value = thresholds.beaconGradient;

      // Hover hit-volume: track the beacon, generous radius so a small/far one is hoverable.
      it.hit.visible = true;
      const hitR = Math.max(0.04, size.width * 4);
      const hitH = Math.max(0.15, size.height + 0.05);
      it.hit.scale.set(hitR, hitH, hitR);
      it.hit.position.set(it.x, baseY + hitH / 2, it.z);

      // Sparkle: a subtle constant stream rising from the base centre outward into a
      // short, randomized dome. On within the sparkle horizon OR while hovered (preview
      // it as a close-by event); density + brightness ramp in as the event nears.
      const sparkDays = thresholds.sparkleHorizon;
      const sparkOn = !reduceMotion && (days <= sparkDays || hovered);
      it.sparkles.visible = sparkOn;
      if (sparkOn) {
        const ramp = hovered ? 1 : 1 - Math.min(1, Math.max(0, days) / sparkDays); // 0 far .. 1 near
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
        it.el.style.color = labelColor;
        it.el.style.textShadow = `0 0 8px ${labelGlow}`;
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
      it.beaconMat.dispose();
      it.sparkGeo.dispose();
      it.sparkMat.dispose();
      if (it.el.parentNode) { it.el.parentNode.removeChild(it.el); }
      if (it.popover.parentNode) { it.popover.parentNode.removeChild(it.popover); }
    }
    coneGeo.dispose(); // shared beacon shapes
    boxGeo.dispose();
    hitGeo.dispose();
    hitMat.dispose();
  };

  return { group, items, update, dispose, hitMeshes };
}
