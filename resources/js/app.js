const mapRoot = document.querySelector('[data-region-map]');
if (mapRoot) {
  import('./region-sandbox/map-page.js')
    .then(({ mountMapPage }) => mountMapPage(mapRoot))
    .catch((err) => console.error('[region-map] failed to load', err));
} else {
  const el = document.querySelector('[data-region-sandbox]');
  if (el) {
    const io = new IntersectionObserver((entries, obs) => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue;
        obs.disconnect();
        import('./region-sandbox/index.js')
          .then(({ mountRegionSandbox }) => mountRegionSandbox(el))
          .catch((err) => {
            console.error('[region-sandbox] failed to load', err);
            el.setAttribute('data-unsupported', '');
          });
      }
    }, { rootMargin: '200px' });
    io.observe(el);
  }
}
