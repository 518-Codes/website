import { mountMapPage } from './map-page.js';

function boot() {
  const root = document.querySelector('[data-region-map]');
  if (root) mountMapPage(root);
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
else boot();
