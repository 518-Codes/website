<?php

use App\Models\Meetup;

it('returns published upcoming events that have coordinates', function () {
    $shown = Meetup::factory()->published()->create([
        'title' => 'Visible Event',
        'slug' => 'visible-event',
        'latitude' => 42.65,
        'longitude' => -73.75,
        'starts_at' => now()->addDays(3),
    ]);

    // Excluded: draft, past, and coord-less.
    Meetup::factory()->draft()->create(['starts_at' => now()->addDays(2)]);
    Meetup::factory()->published()->create(['starts_at' => now()->subDay(), 'ends_at' => now()->subDay()->addHours(3)]);
    Meetup::factory()->published()->create([
        'starts_at' => now()->addDay(),
        'latitude' => null,
        'longitude' => null,
    ]);

    $res = $this->getJson('/api/map-events')->assertOk();

    $res->assertJsonCount(1)
        ->assertJsonPath('0.slug', 'visible-event')
        ->assertJsonPath('0.title', 'Visible Event')
        ->assertJsonPath('0.lat', 42.65)
        ->assertJsonPath('0.lng', -73.75);

    expect($res->json('0.starts_at'))->toBe($shown->starts_at->toIso8601String());
    expect($res->json('0.url'))->toBe(url('/events/visible-event'));
});

it('orders events soonest first', function () {
    Meetup::factory()->published()->create(['slug' => 'later', 'latitude' => 42.0, 'longitude' => -73.9, 'starts_at' => now()->addDays(10)]);
    Meetup::factory()->published()->create(['slug' => 'sooner', 'latitude' => 42.0, 'longitude' => -73.9, 'starts_at' => now()->addDays(2)]);

    $this->getJson('/api/map-events')
        ->assertOk()
        ->assertJsonPath('0.slug', 'sooner')
        ->assertJsonPath('1.slug', 'later');
});
