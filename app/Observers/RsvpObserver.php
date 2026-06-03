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
        $rsvp->load('meetup');

        if ($rsvp->user) {
            $prefs = $rsvp->user->notification_preferences;
            if ($prefs->rsvpConfirmation) {
                $rsvp->user->notify(new RsvpConfirmation($rsvp));
            }
        } else {
            Notification::route('mail', $rsvp->email)
                ->notify(new RsvpConfirmation($rsvp));
        }

        $this->discord->send(
            "🎟️ New RSVP: {$rsvp->name} ({$rsvp->email}) for {$rsvp->meetup->title}"
        );
    }
}
