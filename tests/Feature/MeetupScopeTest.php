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

test('scopeUpcoming still includes a started meetup with no end time inside the grace period', function () {
    Meetup::factory()->create([
        'starts_at' => now()->subHours(2),
        'ends_at' => null,
    ]);

    // Without an explicit end time a meetup stays visible for
    // Meetup::DEFAULT_DURATION_HOURS, so it does not vanish mid-session.
    expect(Meetup::upcoming()->count())->toBe(1);
});

test('scopes can be chained', function () {
    Meetup::factory()->create(['status' => MeetupStatus::Published, 'starts_at' => now()->addDay()]);
    Meetup::factory()->create(['status' => MeetupStatus::Published, 'starts_at' => now()->subDay(), 'ends_at' => now()->subDay()->addHours(3)]);
    Meetup::factory()->create(['status' => MeetupStatus::Draft, 'starts_at' => now()->addDay()]);

    expect(Meetup::published()->upcoming()->count())->toBe(1);
});

test('scopeUpcoming keeps an in-progress meetup that has not ended yet', function () {
    Meetup::factory()->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);

    expect(Meetup::upcoming()->count())->toBe(1);
});

test('scopeUpcoming drops a meetup once it has ended', function () {
    Meetup::factory()->create([
        'starts_at' => now()->subHours(4),
        'ends_at' => now()->subHours(2),
    ]);

    expect(Meetup::upcoming()->count())->toBe(0);
});

test('scopeUpcoming keeps a recently started meetup with no end time', function () {
    Meetup::factory()->create([
        'starts_at' => now()->subHour(),
        'ends_at' => null,
    ]);

    expect(Meetup::upcoming()->count())->toBe(1);
});

test('scopeUpcoming drops a meetup with no end time after the default duration', function () {
    Meetup::factory()->create([
        'starts_at' => now()->subHours(4),
        'ends_at' => null,
    ]);

    expect(Meetup::upcoming()->count())->toBe(0);
});

test('scopePast returns only meetups that have finished', function () {
    Meetup::factory()->create(['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(2)]);
    Meetup::factory()->create(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
    Meetup::factory()->create(['starts_at' => now()->subDay(), 'ends_at' => now()->subDay()->addHours(2)]);
    Meetup::factory()->create(['starts_at' => now()->subDay(), 'ends_at' => null]);

    expect(Meetup::past()->count())->toBe(2);
});

test('upcoming and past scopes partition every meetup exactly once', function () {
    Meetup::factory()->count(5)->create(['starts_at' => now()->addDays(2)]);
    Meetup::factory()->count(3)->create(['starts_at' => now()->subDays(2)]);
    Meetup::factory()->create(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);

    expect(Meetup::upcoming()->count() + Meetup::past()->count())->toBe(Meetup::count());
});
