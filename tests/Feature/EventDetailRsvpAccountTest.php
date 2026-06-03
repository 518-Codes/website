<?php

use App\Enums\MeetupStatus;
use App\Livewire\EventDetail;
use App\Models\Meetup;
use App\Models\Rsvp;
use App\Models\User;
use Livewire\Livewire;

test('authenticated user can rsvp without entering name or email', function () {
    $user = User::factory()->create(['name' => 'Ada Tang', 'email' => 'ada@example.com']);
    $meetup = Meetup::factory()->create(['status' => MeetupStatus::Published, 'starts_at' => now()->addDays(7)]);

    Livewire::actingAs($user)
        ->test(EventDetail::class, ['slug' => $meetup->slug])
        ->assertSet('name', 'Ada Tang')
        ->assertSet('email', 'ada@example.com')
        ->call('rsvp')
        ->assertSet('rsvpd', true)
        ->assertSet('showPasswordPrompt', false);

    $rsvp = Rsvp::where('meetup_id', $meetup->id)->where('email', 'ada@example.com')->first();
    expect($rsvp)->not->toBeNull();
    expect($rsvp->user_id)->toBe($user->id);
});

test('rsvp links to existing user account by email', function () {
    $user = User::factory()->create(['email' => 'ada@example.com', 'username' => 'ada']);
    $meetup = Meetup::factory()->create(['status' => MeetupStatus::Published, 'starts_at' => now()->addDays(7)]);

    Livewire::test(EventDetail::class, ['slug' => $meetup->slug])
        ->set('name', 'Ada Tang')
        ->set('email', 'ada@example.com')
        ->call('rsvp')
        ->assertSet('rsvpd', true)
        ->assertSet('showPasswordPrompt', false);

    $rsvp = Rsvp::where('meetup_id', $meetup->id)->where('email', 'ada@example.com')->first();
    expect($rsvp->user_id)->toBe($user->id);
});

test('rsvp shows password prompt for unknown email', function () {
    $meetup = Meetup::factory()->create(['status' => MeetupStatus::Published, 'starts_at' => now()->addDays(7)]);

    Livewire::test(EventDetail::class, ['slug' => $meetup->slug])
        ->set('name', 'New Person')
        ->set('email', 'new@example.com')
        ->call('rsvp')
        ->assertSet('rsvpd', true)
        ->assertSet('showPasswordPrompt', true);
});

test('creating account from rsvp logs user in and links rsvp', function () {
    $meetup = Meetup::factory()->create(['status' => MeetupStatus::Published, 'starts_at' => now()->addDays(7)]);

    $component = Livewire::test(EventDetail::class, ['slug' => $meetup->slug])
        ->set('name', 'New Person')
        ->set('email', 'new@example.com')
        ->call('rsvp');

    $component
        ->set('newPassword', 'password123')
        ->call('createAccount');

    expect(auth()->check())->toBeTrue();
    expect(auth()->user()->email)->toBe('new@example.com');

    $rsvp = Rsvp::where('meetup_id', $meetup->id)->where('email', 'new@example.com')->first();
    expect($rsvp->user_id)->toBe(auth()->user()->id);
});

test('skipping password prompt leaves rsvp anonymous', function () {
    $meetup = Meetup::factory()->create(['status' => MeetupStatus::Published, 'starts_at' => now()->addDays(7)]);

    Livewire::test(EventDetail::class, ['slug' => $meetup->slug])
        ->set('name', 'Anonymous Person')
        ->set('email', 'anon@example.com')
        ->call('rsvp')
        ->call('skipAccountCreation')
        ->assertSet('showPasswordPrompt', false);

    $rsvp = Rsvp::where('meetup_id', $meetup->id)->where('email', 'anon@example.com')->first();
    expect($rsvp->user_id)->toBeNull();
});
