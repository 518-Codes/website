import { mountRegionSandbox } from './index.js';

const SLIDERS = [
  ['relief', 'setRelief'],
  ['glow', 'setGlow'],
  ['cell', 'setCell'],
  ['light', 'setLight'],
  ['radius', 'setRadius'],
  ['tilt', 'setTilt'],
  ['orbitSpeed', 'setOrbitSpeed'],
  ['bandCount', 'setBandCount'],
  ['bandCurve', 'setBandCurve'],
  ['markerHorizon', 'setMarkerHorizon'],
  ['labelHorizon', 'setLabelHorizon'],
  ['sparkleHorizon', 'setSparkleHorizon'],
  ['sparkleIntensity', 'setSparkleIntensity'],
  ['sparkleSize', 'setSparkleSize'],
  ['sparkleSpeed', 'setSparkleSpeed'],
  ['sparkleVolume', 'setSparkleVolume'],
  ['beaconHeight', 'setBeaconHeight'],
  ['beaconGlow', 'setBeaconGlow'],
  ['beaconGradient', 'setBeaconGradient'],
  ['beaconHue', 'setBeaconHue'],
  ['beaconSaturation', 'setBeaconSaturation'],
  ['easterEggSize', 'setEasterEggSize'],
];
const TOGGLES = [
  ['mono', 'setMono'],
  ['elevation', 'setElevation'],
  ['events', 'setEventsVisible'],
  ['beaconCone', 'setBeaconCone'],
];
const LAYERS = ['road-major', 'road-sub', 'water-river', 'water-stream', 'water-area', 'terrain'];

/** Mounts the sandbox on the /map page and wires the control panel to its API. */
export async function mountMapPage(root) {
  const stage = root.querySelector('[data-region-sandbox]');
  const api = await mountRegionSandbox(stage);
  if (!api) return; // WebGL unsupported -> fallback already shown by mountRegionSandbox

  const vals = api.getValues();

  for (const [key, setter] of SLIDERS) {
    const input = root.querySelector(`#ctl-${key}`);
    const out = root.querySelector(`#val-${key}`);
    if (!input) continue;
    input.value = vals[key];
    if (out) out.textContent = String(vals[key]);
    input.addEventListener('input', () => {
      const v = parseFloat(input.value);
      api[setter](v);
      if (out) out.textContent = input.value;
    });
  }

  for (const [key, setter] of TOGGLES) {
    const cb = root.querySelector(`#ctl-${key}`);
    if (!cb) continue;
    cb.checked = Boolean(vals[key]);
    cb.addEventListener('change', () => api[setter](cb.checked));
  }

  for (const name of LAYERS) {
    const cb = root.querySelector(`#ctl-layer-${name}`);
    if (cb) {
      cb.checked = vals[name] !== false;
      cb.addEventListener('change', () => api.setLayer(name, cb.checked));
    }

    const glow = root.querySelector(`#ctl-glow-${name}`);
    const glowOut = root.querySelector(`#val-glow-${name}`);
    if (glow) {
      glow.value = vals[`glow-${name}`];
      if (glowOut) glowOut.textContent = String(vals[`glow-${name}`]);
      glow.addEventListener('input', () => {
        const v = parseFloat(glow.value);
        api.setLayerGlow(name, v);
        if (glowOut) glowOut.textContent = glow.value;
      });
    }
  }

  const copyBtn = root.querySelector('#ctl-copy');
  const copyOut = root.querySelector('#ctl-copy-out');
  if (copyBtn) {
    copyBtn.addEventListener('click', async () => {
      const json = JSON.stringify(api.getValues(), null, 2);
      try { await navigator.clipboard.writeText(json); } catch (e) { /* clipboard may be blocked */ }
      if (copyOut) copyOut.textContent = json;
    });
  }
}
