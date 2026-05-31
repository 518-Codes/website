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

/** Full Albany→NYC Hudson corridor (tall strip). */
export const CORRIDOR: BBox = { minLng: -74.3, minLat: 40.55, maxLng: -73.4, maxLat: 43.2 };
/** Number of latitude chunks the corridor is split into (north→south). */
export const CHUNK_COUNT = 8;
/** Per-chunk heightmap grid width (height derived to keep ~square cells). */
export const CHUNK_GRID_W = 256;
/** Cities along the corridor (assigned to whichever chunk contains them). */
export const CORRIDOR_CITIES: { name: string; lat: number; lng: number }[] = [
  { name: 'Saratoga', lat: 43.0831, lng: -73.7846 },
  { name: 'Schenectady', lat: 42.8142, lng: -73.9396 },
  { name: 'Troy', lat: 42.7284, lng: -73.6918 },
  { name: 'Albany', lat: 42.6526, lng: -73.7562 },
  { name: 'Hudson', lat: 42.2526, lng: -73.7910 },
  { name: 'Kingston', lat: 41.9270, lng: -73.9974 },
  { name: 'Poughkeepsie', lat: 41.7004, lng: -73.9209 },
  { name: 'Newburgh', lat: 41.5034, lng: -74.0104 },
  { name: 'Peekskill', lat: 41.2901, lng: -73.9204 },
  { name: 'Yonkers', lat: 40.9312, lng: -73.8987 },
  { name: 'New York City', lat: 40.7128, lng: -74.0060 },
];
