<?php

use App\Models\Meetup;
use App\Models\Rsvp;
use App\Models\User;
use App\Notifications\MeetupAnnouncement;
use App\Notifications\RsvpConfirmation;

it('renders what to expect as plain text in the meetup announcement email', function () {
    $meetup = Meetup::factory()->published()->create([
        'what_to_expect' => '<p>Bring a laptop if you&#039;ve got one.</p>',
    ]);

    $html = (string) (new MeetupAnnouncement($meetup))->toMail(User::factory()->create())->render();

    expect($html)
        ->toContain("Bring a laptop if you've got one.")
        ->not->toContain('&#039;')
        ->not->toContain('<p>Bring');
});

it('renders what to expect as plain text in the rsvp confirmation email', function () {
    $meetup = Meetup::factory()->published()->create([
        'what_to_expect' => '<p>Bring a laptop if you&#039;ve got one.</p>',
    ]);
    $rsvp = Rsvp::factory()->for($meetup)->create();

    $html = (string) (new RsvpConfirmation($rsvp))->toMail(User::factory()->create())->render();

    expect($html)
        ->toContain("Bring a laptop if you've got one.")
        ->not->toContain('&#039;')
        ->not->toContain('<p>Bring');
});
