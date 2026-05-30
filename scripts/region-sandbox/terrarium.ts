/** Terrarium RGB → meters. */
export function decodeElevation(r: number, g: number, b: number): number {
  return r * 256 + g + b / 256 - 32768;
}

/** Box-average downsample of a row-major Float32 grid into target dims. */
export function downsample(src: Float32Array, sw: number, sh: number, tw: number, th: number): Float32Array {
  const out = new Float32Array(tw * th);
  for (let ty = 0; ty < th; ty++) {
    for (let tx = 0; tx < tw; tx++) {
      const x0 = Math.floor((tx * sw) / tw);
      const x1 = Math.max(x0 + 1, Math.floor(((tx + 1) * sw) / tw));
      const y0 = Math.floor((ty * sh) / th);
      const y1 = Math.max(y0 + 1, Math.floor(((ty + 1) * sh) / th));
      let sum = 0;
      let count = 0;
      for (let y = y0; y < y1; y++) {
        for (let x = x0; x < x1; x++) {
          sum += src[y * sw + x];
          count++;
        }
      }
      out[ty * tw + tx] = count ? sum / count : 0;
    }
  }
  return out;
}

/** Linear normalize a grid to [0,1] using its own min/max. */
export function normalize(grid: Float32Array): Float32Array {
  let min = Infinity;
  let max = -Infinity;
  for (const v of grid) {
    if (v < min) min = v;
    if (v > max) max = v;
  }
  const range = max - min || 1;
  return grid.map((v) => (v - min) / range);
}
