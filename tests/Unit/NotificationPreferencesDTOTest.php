<?php

use App\DTOs\NotificationPreferences;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('NotificationPreferences has correct defaults', function () {
    $prefs = new NotificationPreferences;

    expect($prefs->rsvpConfirmation)->toBeTrue()
        ->and($prefs->announcements)->toBeTrue()
        ->and($prefs->remindersEnabled)->toBeTrue()
        ->and($prefs->reminderTiming)->toBe(['24h', '1h']);
});

test('NotificationPreferences can be constructed with custom values', function () {
    $prefs = new NotificationPreferences(
        rsvpConfirmation: false,
        announcements: false,
        remindersEnabled: true,
        reminderTiming: ['1h'],
    );

    expect($prefs->rsvpConfirmation)->toBeFalse()
        ->and($prefs->announcements)->toBeFalse()
        ->and($prefs->reminderTiming)->toBe(['1h']);
});

test('User notification_preferences returns NotificationPreferences instance with defaults', function () {
    $user = User::factory()->create();

    expect($user->notification_preferences)->toBeInstanceOf(NotificationPreferences::class)
        ->and($user->notification_preferences->rsvpConfirmation)->toBeTrue();
});

test('User notification_preferences roundtrips through JSON cast', function () {
    $user = User::factory()->create();

    $user->update([
        'notification_preferences' => new NotificationPreferences(
            rsvpConfirmation: false,
            announcements: true,
            remindersEnabled: true,
            reminderTiming: ['24h'],
        ),
    ]);

    $fresh = $user->fresh();

    expect($fresh->notification_preferences->rsvpConfirmation)->toBeFalse()
        ->and($fresh->notification_preferences->announcements)->toBeTrue()
        ->and($fresh->notification_preferences->reminderTiming)->toBe(['24h']);
});
