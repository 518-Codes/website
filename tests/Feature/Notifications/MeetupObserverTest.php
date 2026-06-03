<?php

use App\DTOs\NotificationPreferences;
use App\Enums\MeetupStatus;
use App\Jobs\SendMeetupAnnouncements;
use App\Models\Meetup;
use App\Models\User;
use App\Notifications\MeetupAnnouncement;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

test('MeetupObserver sends MeetupAnnouncement when status transitions to Published', function () {
    Bus::fake();
    $users = User::factory()->count(3)->create();
    $meetup = Meetup::factory()->create(['status' => MeetupStatus::Draft]);

    $meetup->update(['status' => MeetupStatus::Published]);

    Bus::assertDispatched(SendMeetupAnnouncements::class, fn ($job) => $job->meetup->is($meetup));
});

test('MeetupObserver does not send announcement on non-status save', function () {
    Bus::fake();
    User::factory()->count(2)->create();
    $meetup = Meetup::factory()->create(['status' => MeetupStatus::Published]);

    $meetup->update(['title' => 'Updated Title']);

    Bus::assertNotDispatched(SendMeetupAnnouncements::class);
});

test('MeetupObserver does not send announcement on second Published save', function () {
    Bus::fake();
    User::factory()->count(2)->create();
    $meetup = Meetup::factory()->create(['status' => MeetupStatus::Published]);

    $meetup->update(['status' => MeetupStatus::Published]);

    Bus::assertNotDispatched(SendMeetupAnnouncements::class);
});

test('MeetupObserver skips users with announcements preference disabled', function () {
    $optedIn = User::factory()->create();
    $optedOut = User::factory()->create([
        'notification_preferences' => new NotificationPreferences(announcements: false),
    ]);
    $meetup = Meetup::factory()->create(['status' => MeetupStatus::Draft]);

    SendMeetupAnnouncements::dispatch($meetup);

    Notification::assertSentTo($optedIn, MeetupAnnouncement::class);
    Notification::assertNotSentTo($optedOut, MeetupAnnouncement::class);
});
