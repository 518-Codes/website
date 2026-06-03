<?php

use App\DTOs\NotificationPreferences;
use App\Enums\MeetupStatus;
use App\Models\Meetup;
use App\Models\Rsvp;
use App\Models\User;
use App\Notifications\MeetupReminder;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

test('SendMeetupReminders --timing=24h notifies opted-in users 24h before', function () {
    $user = User::factory()->create();
    $meetup = Meetup::factory()->create([
        'status' => MeetupStatus::Published,
        'starts_at' => now()->addHours(24),
    ]);
    Rsvp::factory()->create(['meetup_id' => $meetup->id, 'user_id' => $user->id, 'email' => $user->email]);

    $this->artisan('notifications:send-reminders --timing=24h');

    Notification::assertSentTo($user, MeetupReminder::class, fn ($n) => $n->timing === '24h');
});

test('SendMeetupReminders --timing=1h notifies opted-in users 1h before', function () {
    $user = User::factory()->create();
    $meetup = Meetup::factory()->create([
        'status' => MeetupStatus::Published,
        'starts_at' => now()->addHour(),
    ]);
    Rsvp::factory()->create(['meetup_id' => $meetup->id, 'user_id' => $user->id, 'email' => $user->email]);

    $this->artisan('notifications:send-reminders --timing=1h');

    Notification::assertSentTo($user, MeetupReminder::class, fn ($n) => $n->timing === '1h');
});

test('SendMeetupReminders skips users who have opted out of reminders', function () {
    $user = User::factory()->create([
        'notification_preferences' => new NotificationPreferences(remindersEnabled: false),
    ]);
    $meetup = Meetup::factory()->create([
        'status' => MeetupStatus::Published,
        'starts_at' => now()->addHours(24),
    ]);
    Rsvp::factory()->create(['meetup_id' => $meetup->id, 'user_id' => $user->id, 'email' => $user->email]);

    $this->artisan('notifications:send-reminders --timing=24h');

    Notification::assertNotSentTo($user, MeetupReminder::class);
});

test('SendMeetupReminders skips users who have excluded that timing', function () {
    $user = User::factory()->create([
        'notification_preferences' => new NotificationPreferences(remindersEnabled: true, reminderTiming: ['1h']),
    ]);
    $meetup = Meetup::factory()->create([
        'status' => MeetupStatus::Published,
        'starts_at' => now()->addHours(24),
    ]);
    Rsvp::factory()->create(['meetup_id' => $meetup->id, 'user_id' => $user->id, 'email' => $user->email]);

    $this->artisan('notifications:send-reminders --timing=24h');

    Notification::assertNotSentTo($user, MeetupReminder::class);
});

test('SendMeetupReminders does not double-send when run twice in same window', function () {
    $user = User::factory()->create();
    $meetup = Meetup::factory()->create([
        'status' => MeetupStatus::Published,
        'starts_at' => now()->addHours(24),
    ]);
    Rsvp::factory()->create(['meetup_id' => $meetup->id, 'user_id' => $user->id, 'email' => $user->email]);

    $this->artisan('notifications:send-reminders --timing=24h');
    $this->artisan('notifications:send-reminders --timing=24h');

    Notification::assertSentToTimes($user, MeetupReminder::class, 1);
});
