<?php

use App\Models\Meetup;
use App\Models\User;
use App\Notifications\MeetupAnnouncement;
use App\Notifications\MeetupReminder;
use App\Notifications\RsvpConfirmation;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

it('sends all four preview emails when type is all', function () {
    $user = User::factory()->create();
    Meetup::factory()->create();

    $this->artisan('mail:preview', ['type' => 'all'])
        ->assertSuccessful();

    Notification::assertSentOnDemand(RsvpConfirmation::class);
    Notification::assertSentTo($user, MeetupAnnouncement::class);
    Notification::assertSentTo($user, MeetupReminder::class, fn ($n) => $n->timing === '24h');
    Notification::assertSentTo($user, MeetupReminder::class, fn ($n) => $n->timing === '1h');
});

it('sends only the rsvp-confirmation when specified', function () {
    User::factory()->create();
    Meetup::factory()->create();

    $this->artisan('mail:preview', ['type' => 'rsvp-confirmation'])
        ->assertSuccessful();

    Notification::assertSentOnDemand(RsvpConfirmation::class);
    Notification::assertNotSentTo(User::first(), MeetupAnnouncement::class);
});

it('sends only meetup-announcement when specified', function () {
    $user = User::factory()->create();
    Meetup::factory()->create();

    $this->artisan('mail:preview', ['type' => 'meetup-announcement'])
        ->assertSuccessful();

    Notification::assertSentTo($user, MeetupAnnouncement::class);
    Notification::assertNotSentTo($user, MeetupReminder::class);
});

it('sends meetup-reminder-24h when specified', function () {
    $user = User::factory()->create();
    Meetup::factory()->create();

    $this->artisan('mail:preview', ['type' => 'meetup-reminder-24h'])
        ->assertSuccessful();

    Notification::assertSentTo($user, MeetupReminder::class, fn ($n) => $n->timing === '24h');
});

it('sends meetup-reminder-1h when specified', function () {
    $user = User::factory()->create();
    Meetup::factory()->create();

    $this->artisan('mail:preview', ['type' => 'meetup-reminder-1h'])
        ->assertSuccessful();

    Notification::assertSentTo($user, MeetupReminder::class, fn ($n) => $n->timing === '1h');
});

it('fails with an error for an invalid type', function () {
    $this->artisan('mail:preview', ['type' => 'nonexistent-type'])
        ->assertFailed();
});

it('uses factory data when no users or meetups exist', function () {
    $this->artisan('mail:preview', ['type' => 'meetup-announcement'])
        ->assertSuccessful();
});
