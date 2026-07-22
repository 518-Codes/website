<?php

use App\Enums\MeetupStatus;
use App\Models\Meetup;

test('scopePublished returns only published meetups', function () {
    Meetup::factory()->create(['status' => MeetupStatus::Published]);
    Meetup::factory()->create(['status' => MeetupStatus::Draft]);
    Meetup::factory()->create(['status' => MeetupStatus::Cancelled]);

    expect(Meetup::published()->count())->toBe(1);
});

test('scopeUpcoming returns meetups starting from now onwards', function () {
    Meetup::factory()->create(['starts_at' => now()->addDay()]);
    Meetup::factory()->create(['starts_at' => now()->subDay(), 'ends_at' => now()->subDay()->addHours(3)]);

    expect(Meetup::upcoming()->count())->toBe(1);
});

test('scopeUpcoming includes meetups currently in progress', function () {
    Meetup::factory()->create([
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->addHours(2),
    ]);
    Meetup::factory()->create([
        'starts_at' => now()->subDay(),
        'ends_at' => now()->subDay()->addHours(3),
    ]);

    expect(Meetup::upcoming()->count())->toBe(1);
});

test('scopeUpcoming excludes started meetups with no end time', function () {
    Meetup::factory()->create([
        'starts_at' => now()->subHours(2),
        'ends_at' => null,
    ]);

    expect(Meetup::upcoming()->count())->toBe(0);
});

test('scopes can be chained', function () {
    Meetup::factory()->create(['status' => MeetupStatus::Published, 'starts_at' => now()->addDay()]);
    Meetup::factory()->create(['status' => MeetupStatus::Published, 'starts_at' => now()->subDay(), 'ends_at' => now()->subDay()->addHours(3)]);
    Meetup::factory()->create(['status' => MeetupStatus::Draft, 'starts_at' => now()->addDay()]);

    expect(Meetup::published()->upcoming()->count())->toBe(1);
});
