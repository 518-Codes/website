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
    Meetup::factory()->create(['starts_at' => now()->subDay()]);

    expect(Meetup::upcoming()->count())->toBe(1);
});

test('scopes can be chained', function () {
    Meetup::factory()->create(['status' => MeetupStatus::Published, 'starts_at' => now()->addDay()]);
    Meetup::factory()->create(['status' => MeetupStatus::Published, 'starts_at' => now()->subDay()]);
    Meetup::factory()->create(['status' => MeetupStatus::Draft, 'starts_at' => now()->addDay()]);

    expect(Meetup::published()->upcoming()->count())->toBe(1);
});
