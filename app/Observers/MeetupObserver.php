<?php

namespace App\Observers;

use App\Enums\MeetupStatus;
use App\Models\Meetup;
use App\Models\User;
use App\Notifications\MeetupAnnouncement;

class MeetupObserver
{
    public function updated(Meetup $meetup): void
    {
        if (! $meetup->wasChanged('status')) {
            return;
        }

        if ($meetup->status !== MeetupStatus::Published) {
            return;
        }

        if ($meetup->getOriginal('status') === MeetupStatus::Published->value) {
            return;
        }

        User::query()
            ->get()
            ->filter(fn ($user) => $user->notification_preferences->announcements)
            ->each(fn ($user) => $user->notify(new MeetupAnnouncement($meetup)));
    }
}
