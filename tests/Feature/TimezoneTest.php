<?php

use App\Models\Meetup;

test('the app runs in the capital region timezone', function () {
    expect(config('app.timezone'))->toBe('America/New_York');
});

test('an evening event entered as local time is in progress during that evening', function () {
    // A 7pm-10pm event on a summer evening, entered the way an organizer types it.
    $meetup = Meetup::factory()->published()->create([
        'starts_at' => '2026-09-04 19:00:00',
        'ends_at' => '2026-09-04 22:00:00',
    ]);

    // 8:30pm local, squarely inside the event.
    $this->travelTo('2026-09-04 20:30:00');

    expect($meetup->fresh()->isInProgress())->toBeTrue()
        ->and(Meetup::upcoming()->where('id', $meetup->id)->exists())->toBeTrue();
});

test('an evening event has ended by the following morning', function () {
    $meetup = Meetup::factory()->published()->create([
        'starts_at' => '2026-09-04 19:00:00',
        'ends_at' => '2026-09-04 22:00:00',
    ]);

    $this->travelTo('2026-09-05 09:00:00');

    expect($meetup->fresh()->hasEnded())->toBeTrue()
        ->and(Meetup::past()->where('id', $meetup->id)->exists())->toBeTrue();
});
