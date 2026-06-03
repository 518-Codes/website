<?php

namespace App\Observers;

use App\Models\Rsvp;
use App\Notifications\RsvpConfirmation;
use App\Services\DiscordWebhook;
use Illuminate\Support\Facades\Notification;

class RsvpObserver
{
    public function __construct(private readonly DiscordWebhook $discord) {}

    public function created(Rsvp $rsvp): void
    {
        $rsvp->load('meetup', 'user');

        if ($rsvp->user) {
            $prefs = $rsvp->user->notification_preferences;
            if ($prefs->rsvpConfirmation) {
                $rsvp->user->notify(new RsvpConfirmation($rsvp));
            }
        } else {
            Notification::route('mail', $rsvp->email)
                ->notify(new RsvpConfirmation($rsvp));
        }

        $meetupAdminUrl = route('filament.admin.resources.meetups.edit', ['record' => $rsvp->meetup->id]);
        $rsvpCount = $rsvp->meetup->rsvps()->count();

        $fields = [
            ['name' => 'Name', 'value' => $rsvp->name, 'inline' => true],
            ['name' => 'Email', 'value' => $rsvp->email, 'inline' => true],
            ['name' => 'Account', 'value' => $rsvp->user ? "@{$rsvp->user->username}" : 'Guest', 'inline' => true],
            ['name' => 'Event', 'value' => "[{$rsvp->meetup->title}]({$meetupAdminUrl})", 'inline' => true],
            ['name' => 'Event Date', 'value' => $rsvp->meetup->starts_at->format('M j, Y g:ia'), 'inline' => true],
            ['name' => 'Total RSVPs', 'value' => (string) $rsvpCount, 'inline' => true],
        ];

        $this->discord->sendEmbed('🎟️ New RSVP', [
            'title' => $rsvp->name,
            'color' => 0x5EFC8D,
            'fields' => $fields,
        ]);
    }
}
