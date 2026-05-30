<?php

// Server-render smoke test for the region-sandbox panel on the homepage.
// JS/WebGL/console behavior is verified manually in a real browser;
// a browser-capable CI (Playwright/Dusk) would be needed to automate that layer.

test('homepage renders the region sandbox panel shell', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('data-region-sandbox', false)
        ->assertSee('capital-region.map')
        ->assertSee('region-canvas', false)
        ->assertSee('region-stage', false)
        ->assertSee('region-map.png', false);
});
