<?php

use App\DTOs\NotificationPreferences;
use App\Enums\MeetupStatus;
use App\Models\Meetup;
use App\Models\User;
use App\Notifications\MeetupAnnouncement;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

test('MeetupObserver sends MeetupAnnouncement when status transitions to Published', function () {
    $users = User::factory()->count(3)->create();
    $meetup = Meetup::factory()->create(['status' => MeetupStatus::Draft]);

    $meetup->update(['status' => MeetupStatus::Published]);

    Notification::assertSentTo($users, MeetupAnnouncement::class, fn ($n) => $n->meetup->is($meetup));
});

test('MeetupObserver does not send announcement on non-status save', function () {
    User::factory()->count(2)->create();
    $meetup = Meetup::factory()->create(['status' => MeetupStatus::Published]);

    $meetup->update(['title' => 'Updated Title']);

    Notification::assertNothingSent();
});

test('MeetupObserver does not send announcement on second Published save', function () {
    User::factory()->count(2)->create();
    $meetup = Meetup::factory()->create(['status' => MeetupStatus::Published]);

    $meetup->update(['status' => MeetupStatus::Published]);

    Notification::assertNothingSent();
});

test('MeetupObserver skips users with announcements preference disabled', function () {
    $optedIn = User::factory()->create();
    $optedOut = User::factory()->create([
        'notification_preferences' => new NotificationPreferences(announcements: false),
    ]);
    $meetup = Meetup::factory()->create(['status' => MeetupStatus::Draft]);

    $meetup->update(['status' => MeetupStatus::Published]);

    Notification::assertSentTo($optedIn, MeetupAnnouncement::class);
    Notification::assertNotSentTo($optedOut, MeetupAnnouncement::class);
});
