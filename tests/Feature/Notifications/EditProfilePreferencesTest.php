<?php

use App\DTOs\NotificationPreferences;
use App\Livewire\Members\EditProfile;
use App\Models\User;
use Livewire\Livewire;

test('edit profile loads existing notification preferences', function () {
    $user = User::factory()->create([
        'notification_preferences' => new NotificationPreferences(
            announcements: false,
            remindersEnabled: true,
            reminderTiming: ['1h'],
        ),
    ]);

    Livewire::actingAs($user)
        ->test(EditProfile::class, ['username' => $user->username])
        ->assertSet('prefAnnouncements', false)
        ->assertSet('prefRemindersEnabled', true)
        ->assertSet('prefReminderTiming', ['1h']);
});

test('edit profile saves notification preferences', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(EditProfile::class, ['username' => $user->username])
        ->set('prefAnnouncements', false)
        ->set('prefRemindersEnabled', true)
        ->set('prefReminderTiming', ['24h'])
        ->call('save');

    $fresh = $user->fresh();
    expect($fresh->notification_preferences->announcements)->toBeFalse()
        ->and($fresh->notification_preferences->reminderTiming)->toBe(['24h']);
});

test('edit profile requires at least one reminder timing when reminders are enabled', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(EditProfile::class, ['username' => $user->username])
        ->set('prefRemindersEnabled', true)
        ->set('prefReminderTiming', [])
        ->call('save')
        ->assertHasErrors(['prefReminderTiming']);
});
