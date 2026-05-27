<?php

use App\Enums\MeetupStatus;
use App\Models\Meetup;

test('home page returns 200', function () {
    $this->get('/')->assertOk();
});

test('events listing page returns 200', function () {
    $this->get('/events')->assertOk();
});

test('event detail returns 200 for a published meetup', function () {
    $meetup = Meetup::factory()->create([
        'status' => MeetupStatus::Published,
        'starts_at' => now()->addDays(5),
    ]);

    $this->get("/events/{$meetup->slug}")->assertOk();
});

test('event detail returns 404 for unknown slug', function () {
    $this->get('/events/does-not-exist')->assertNotFound();
});
