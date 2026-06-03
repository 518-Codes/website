<?php

namespace App\Observers;

use App\Enums\MeetupStatus;
use App\Jobs\SendMeetupAnnouncements;
use App\Models\Meetup;

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

        SendMeetupAnnouncements::dispatch($meetup);
    }
}
