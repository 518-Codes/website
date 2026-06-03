<?php

namespace App\Observers;

use App\Enums\MeetupStatus;
use App\Models\Meetup;
use App\Models\User;
use App\Notifications\MeetupAnnouncement;
use App\Services\DiscordWebhook;

class MeetupObserver
{
    public function __construct(private readonly DiscordWebhook $discord) {}

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

        $adminUrl = route('filament.admin.resources.meetups.edit', ['record' => $meetup->id]);
        $eventUrl = route('events.show', $meetup->slug);
        $rsvpCount = $meetup->rsvps()->count();

        $this->discord->sendEmbed('🚀 Meetup published', [
            'title' => $meetup->title,
            'url' => $adminUrl,
            'color' => 0x5EFC8D,
            'fields' => [
                ['name' => 'Date', 'value' => $meetup->starts_at->format('M j, Y g:ia'), 'inline' => true],
                ['name' => 'Location', 'value' => $meetup->location, 'inline' => true],
                ['name' => 'RSVPs so far', 'value' => (string) $rsvpCount, 'inline' => true],
                ['name' => 'Public URL', 'value' => $eventUrl, 'inline' => false],
            ],
        ]);

        User::query()
            ->get()
            ->filter(fn (User $user) => $user->notification_preferences->announcements)
            ->each(fn (User $user) => $user->notify(new MeetupAnnouncement($meetup)));
    }
}
