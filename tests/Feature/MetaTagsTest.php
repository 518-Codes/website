<?php

test('home page renders favicon links and open graph metadata', function () {
    $response = $this->get('/');

    $response->assertOk();

    // Icons + manifest + theme color
    $response->assertSee('rel="icon"', false);
    $response->assertSee('/favicon.svg', false);
    $response->assertSee('rel="apple-touch-icon"', false);
    $response->assertSee('/site.webmanifest', false);
    $response->assertSee('name="theme-color"', false);

    // Open Graph + Twitter card, with an ABSOLUTE og:image URL
    $response->assertSee('property="og:title"', false);
    $response->assertSee('property="og:image"', false);
    $response->assertSee(url('/og-image.png'), false);
    $response->assertSee('name="twitter:card"', false);
    $response->assertSee('summary_large_image', false);

    // Default copy
    $response->assertSee('518.codes — Upstate NY Developer Group', false);

    // Twitter handle attribution was intentionally dropped
    $response->assertDontSee('twitter:site', false);
    $response->assertDontSee('twitter:creator', false);
});
