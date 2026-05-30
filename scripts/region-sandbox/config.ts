/** Geographic + grid configuration for the region sandbox build. */
export type BBox = { minLng: number; minLat: number; maxLng: number; maxLat: number };

/** Capital Region bbox framing Albany, Troy, Schenectady, Saratoga. Tune after first render. */
export const BBOX: BBox = {
  minLng: -74.1,
  minLat: 42.5,
  maxLng: -73.4,
  maxLat: 43.2,
};

/** Terrarium tile zoom level used to source elevation. */
export const DEM_ZOOM = 10;

/** Output grid resolution (4:3). Glyph cells downstream are coarser than this. */
export const GRID_W = 256;
export const GRID_H = 192;

/** Cities rendered as labels, from the region-map spec. */
export const CITIES: { name: string; lat: number; lng: number }[] = [
  { name: 'Albany', lat: 42.6526, lng: -73.7562 },
  { name: 'Troy', lat: 42.7284, lng: -73.6918 },
  { name: 'Schenectady', lat: 42.8142, lng: -73.9396 },
  { name: 'Saratoga', lat: 43.0831, lng: -73.7846 },
];

export const OUTPUT_DIR = 'public/region-sandbox';
