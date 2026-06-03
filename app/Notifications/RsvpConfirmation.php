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

    public function __construct(public readonly Rsvp $rsvp) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You're registered for {$this->rsvp->meetup->title}!")
            ->greeting("Hey {$this->rsvp->name}!")
            ->line("You're confirmed for **{$this->rsvp->meetup->title}**.")
            ->line('📅 '.$this->rsvp->meetup->starts_at->format('l, F j, Y \a\t g:ia'))
            ->line('📍 '.$this->rsvp->meetup->location)
            ->action('View Event', route('events.show', $this->rsvp->meetup->slug))
            ->line('See you there!');
    }
}
