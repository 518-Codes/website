<?php

use App\Enums\MeetupStatus;
use App\Livewire\EventDetail;
use App\Models\Meetup;
use App\Models\Rsvp;
use Livewire\Livewire;

test('event detail page loads for a published meetup', function () {
    $meetup = Meetup::factory()->create([
        'status' => MeetupStatus::Published,
        'starts_at' => now()->addDays(7),
    ]);

    $this->get("/events/{$meetup->slug}")->assertOk();
});

test('event detail returns 404 for a draft meetup', function () {
    $meetup = Meetup::factory()->create([
        'status' => MeetupStatus::Draft,
        'starts_at' => now()->addDays(7),
    ]);

    $this->get("/events/{$meetup->slug}")->assertNotFound();
});

test('rsvp action creates an rsvp record and sets rsvpd to true', function () {
    $meetup = Meetup::factory()->create([
        'status' => MeetupStatus::Published,
        'starts_at' => now()->addDays(7),
    ]);

    Livewire::test(EventDetail::class, ['slug' => $meetup->slug])
        ->set('name', 'Ada Tang')
        ->set('email', 'ada@example.com')
        ->call('rsvp')
        ->assertSet('rsvpd', true);

    expect(Rsvp::where('meetup_id', $meetup->id)->where('email', 'ada@example.com')->exists())->toBeTrue();
});

test('rsvp action requires name and email', function () {
    $meetup = Meetup::factory()->create([
        'status' => MeetupStatus::Published,
        'starts_at' => now()->addDays(7),
    ]);

    Livewire::test(EventDetail::class, ['slug' => $meetup->slug])
        ->set('name', '')
        ->set('email', '')
        ->call('rsvp')
        ->assertHasErrors(['name' => 'required', 'email' => 'required']);
});
