/** Tunable thresholds + beacon size envelope. Days. World units for heights. */
export const EVENT_DEFAULTS = {
  markerHorizon: 30,
  labelHorizon: 14,
  sparkleHorizon: 7,
  spotlight: 2,
  beaconMinHeight: 0.12,
  beaconMaxHeight: 0.25,
  beaconGlow: 1.05,
  beaconGradient: 0.85, // base->top dimming (0 = flat, ~0.95 = top near-black)
  beaconHue: 39, // degrees; ~warm gold by default (matches the original 0xffd27f)
  beaconSaturation: 1.0, // 0 = grayscale, 1 = full color
  beaconCone: true, // true = truncated cone, false = square box
  sparkleIntensity: 5,
  sparkleSize: 0.008,
  sparkleSpeed: 0.95,
  sparkleVolume: 0.92,
  spotlightBoost: 1.6,
  easterEggSize: 0.12, // spotlight shaft height (world units); ~the smallest event beacon
};

/** Fractional days from `nowMs` to `startsAtMs`. */
export function recencyDays(startsAtMs, nowMs) {
  return (startsAtMs - nowMs) / 86400000;
}

/**
 * Map days-until-event to a beacon size envelope.
 * Linear from max (now) to min (marker horizon); hidden beyond; boosted in spotlight.
 *
 * @returns {{ visible: boolean, height?: number, width?: number, glow?: number, norm?: number, spotlight?: boolean }}
 */
export function recencyToSize(days, t = EVENT_DEFAULTS) {
  if (days > t.markerHorizon) {
    return { visible: false };
  }
  const norm = 1 - Math.min(1, Math.max(0, days) / t.markerHorizon); // 1 now -> 0 edge
  const spotlight = days <= t.spotlight;
  const boost = spotlight ? t.spotlightBoost : 1;

  const height = (t.beaconMinHeight + (t.beaconMaxHeight - t.beaconMinHeight) * norm) * boost;
  const width = (0.006 + 0.02 * norm) * boost;
  const glow = t.beaconGlow * (0.4 + 0.6 * norm) * (spotlight ? 1.5 : 1);

  return { visible: true, height, width, glow, norm, spotlight };
}

/** Human countdown text from fractional days-until-event. */
export function countdownLabel(days) {
  if (days < 1) {
    return 'today';
  }
  if (days < 2) {
    return 'tomorrow';
  }
  return `in ${Math.round(days)} days`;
}

/**
 * Group events by location string; each group sorted soonest-first with a `soonest`.
 *
 * @param {Array<{location:string,lat:number,lng:number,startsAtMs:number,x?:number,z?:number}>} events
 * @returns {Array<{key:string,location:string,lat:number,lng:number,x?:number,z?:number,events:Array,soonest:object}>}
 */
export function groupByLocation(events) {
  const map = new Map();
  for (const ev of events) {
    const key = ev.location ?? `${ev.lat},${ev.lng}`;
    if (!map.has(key)) {
      // x/z are chunk-local coords (set by the caller before grouping); co-located
      // events share them, so the representative event's values stand for the group.
      map.set(key, { key, location: ev.location, lat: ev.lat, lng: ev.lng, x: ev.x, z: ev.z, events: [] });
    }
    map.get(key).events.push(ev);
  }
  const groups = [...map.values()];
  for (const g of groups) {
    g.events.sort((a, b) => a.startsAtMs - b.startsAtMs);
    g.soonest = g.events[0];
  }
  return groups;
}

/**
 * Project an event's lng/lat into the corridor: which chunk (by bbox) + chunk-local [0,1].
 * Works for both lat-band (mainline) and lng-band (longisland) chunks. Returns null if the
 * event lies outside every chunk bbox.
 *
 * @param {{lat:number,lng:number}} ev
 * @param {{chunks:Array<{index:number,bbox:{minLng:number,minLat:number,maxLng:number,maxLat:number}}>}} manifest
 * @returns {{chunkIndex:number,x:number,z:number}|null}
 */
export function projectEventToCorridor(ev, manifest) {
  for (let i = 0; i < manifest.chunks.length; i++) {
    const b = manifest.chunks[i].bbox;
    if (ev.lng >= b.minLng && ev.lng <= b.maxLng && ev.lat >= b.minLat && ev.lat <= b.maxLat) {
      return {
        chunkIndex: i,
        x: (ev.lng - b.minLng) / (b.maxLng - b.minLng),
        z: (b.maxLat - ev.lat) / (b.maxLat - b.minLat),
      };
    }
  }
  return null;
}
