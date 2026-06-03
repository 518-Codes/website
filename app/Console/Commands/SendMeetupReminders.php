<?php

namespace App\Console\Commands;

use App\Enums\MeetupStatus;
use App\Models\Meetup;
use App\Models\Rsvp;
use App\Notifications\MeetupReminder;
use Illuminate\Console\Command;

class SendMeetupReminders extends Command
{
    protected $signature = 'notifications:send-reminders {--timing=24h : Either 24h or 1h}';

    protected $description = 'Send meetup reminder notifications to opted-in attendees';

    public function handle(): void
    {
        $timing = $this->option('timing');

        [$windowStart, $windowEnd, $sentAtColumn] = match ($timing) {
            '24h' => [now()->addMinutes(23 * 60 + 45), now()->addMinutes(24 * 60 + 15), 'reminder_24h_sent_at'],
            '1h' => [now()->addMinutes(45), now()->addMinutes(75), 'reminder_1h_sent_at'],
            default => $this->fail("Invalid timing: {$timing}"),
        };

        Meetup::query()
            ->where('status', MeetupStatus::Published)
            ->whereBetween('starts_at', [$windowStart, $windowEnd])
            ->with(['rsvps.user'])
            ->each(function (Meetup $meetup) use ($timing, $sentAtColumn) {
                $meetup->rsvps
                    ->filter(fn (Rsvp $rsvp) => $rsvp->user !== null)
                    ->filter(fn (Rsvp $rsvp) => $rsvp->{$sentAtColumn} === null)
                    ->filter(function (Rsvp $rsvp) use ($timing) {
                        $prefs = $rsvp->user->notification_preferences;

                        return $prefs->remindersEnabled && in_array($timing, $prefs->reminderTiming);
                    })
                    ->unique(fn (Rsvp $rsvp) => $rsvp->user_id)
                    ->each(function (Rsvp $rsvp) use ($meetup, $timing, $sentAtColumn) {
                        $rsvp->user->notify(new MeetupReminder($meetup, $timing));
                        $rsvp->update([$sentAtColumn => now()]);
                    });
            });
    }
}
