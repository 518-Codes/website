<?php

namespace App\Notifications;

use App\Models\Rsvp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RsvpConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public readonly Rsvp $rsvp) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $meetup = $this->rsvp->meetup;
        $timeRange = $meetup->ends_at
            ? $meetup->starts_at->format('l, F j, Y \a\t g:ia').' – '.$meetup->ends_at->format('g:ia')
            : $meetup->starts_at->format('l, F j, Y \a\t g:ia');

        $mapsUrl = $meetup->latitude && $meetup->longitude
            ? "https://www.google.com/maps?q={$meetup->latitude},{$meetup->longitude}"
            : null;

        $message = (new MailMessage)
            ->subject("You're registered for {$meetup->title}!")
            ->greeting("Hey {$this->rsvp->name}!")
            ->line("You're confirmed for **{$meetup->title}**.")
            ->line("**When:** {$timeRange}")
            ->line("**Where:** {$meetup->location}");

        if ($mapsUrl) {
            $message->line("[Get directions]({$mapsUrl})");
        }

        if ($meetup->what_to_expect) {
            $message->line("**What to expect:** {$meetup->what_to_expect}");
        }

        return $message
            ->action('View Event', route('events.show', $meetup->slug))
            ->line('See you there!');
    }
}
