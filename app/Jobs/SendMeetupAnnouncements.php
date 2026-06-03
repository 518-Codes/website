<?php

namespace App\Jobs;

use App\Models\Meetup;
use App\Models\User;
use App\Notifications\MeetupAnnouncement;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMeetupAnnouncements implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Meetup $meetup)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        User::query()
            ->get()
            ->filter(fn (User $user) => $user->notification_preferences->announcements)
            ->each(fn (User $user) => $user->notify(new MeetupAnnouncement($this->meetup)));
    }
}
