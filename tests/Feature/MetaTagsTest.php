<?php

use App\Enums\MeetupStatus;
use App\Models\Meetup;

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

test('event detail page renders per-event open graph title and description', function () {
    $meetup = Meetup::factory()->create([
        'status' => MeetupStatus::Published,
        'title' => 'Intro to Rust',
        'description' => '<p>Come learn <strong>Rust</strong> with us at the library.</p>',
        'starts_at' => now()->addDays(7),
    ]);

    $response = $this->get('/events/'.$meetup->slug);

    $response->assertOk();
    $response->assertSee('property="og:title"', false);
    $response->assertSee('Intro to Rust', false);
    // HTML stripped from the description for the social card
    $response->assertSee('<meta property="og:description" content="Come learn Rust with us at the library."', false);
});
