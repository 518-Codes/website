<?php

use App\DTOs\NotificationPreferences;
use App\Enums\MeetupStatus;
use App\Livewire\EventDetail;
use App\Models\Meetup;
use App\Models\User;
use App\Notifications\RsvpConfirmation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    Http::fake();
});

test('RsvpObserver sends RsvpConfirmation to a registered user', function () {
    $user = User::factory()->create();
    $meetup = Meetup::factory()->create([
        'status' => MeetupStatus::Published,
        'starts_at' => now()->addDays(7),
    ]);

    Livewire::test(EventDetail::class, ['slug' => $meetup->slug])
        ->set('name', $user->name)
        ->set('email', $user->email)
        ->call('rsvp');

    Notification::assertSentTo($user, RsvpConfirmation::class);
});

test('RsvpObserver sends RsvpConfirmation to a guest email', function () {
    $meetup = Meetup::factory()->create([
        'status' => MeetupStatus::Published,
        'starts_at' => now()->addDays(7),
    ]);

    Livewire::test(EventDetail::class, ['slug' => $meetup->slug])
        ->set('name', 'Guest User')
        ->set('email', 'guest@example.com')
        ->call('rsvp');

    Notification::assertSentOnDemand(RsvpConfirmation::class, function ($notification, $channels, $notifiable) {
        return $notifiable->routes['mail'] === 'guest@example.com';
    });
});

test('RsvpObserver sends Discord alert on RSVP created', function () {
    config(['services.discord.webhook_url' => 'https://discord.test/webhook']);

    $meetup = Meetup::factory()->create([
        'status' => MeetupStatus::Published,
        'starts_at' => now()->addDays(7),
        'title' => 'Test Meetup',
    ]);

    Livewire::test(EventDetail::class, ['slug' => $meetup->slug])
        ->set('name', 'Ada Tang')
        ->set('email', 'ada@example.com')
        ->call('rsvp');

    Http::assertSent(fn ($request) => str_contains($request->body(), 'Ada Tang') &&
        str_contains($request->body(), 'Test Meetup')
    );
});

test('RsvpObserver does not send notification when user has opted out of RSVP confirmations', function () {
    $user = User::factory()->create([
        'notification_preferences' => new NotificationPreferences(rsvpConfirmation: false),
    ]);
    $meetup = Meetup::factory()->create([
        'status' => MeetupStatus::Published,
        'starts_at' => now()->addDays(7),
    ]);

    Livewire::test(EventDetail::class, ['slug' => $meetup->slug])
        ->set('name', $user->name)
        ->set('email', $user->email)
        ->call('rsvp');

    Notification::assertNothingSentTo($user);
});
