<?php

use App\Models\Meetup;

use function Pest\Laravel\get;

it('downloads an ics file for a published meetup', function () {
    $meetup = Meetup::factory()->published()->create();

    get(route('events.ics', $meetup->slug))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
        ->assertSee('BEGIN:VCALENDAR')
        ->assertSee('BEGIN:VEVENT')
        ->assertSee($meetup->title);
});

it('returns 404 for a draft meetup', function () {
    $meetup = Meetup::factory()->draft()->create();

    get(route('events.ics', $meetup->slug))
        ->assertNotFound();
});
