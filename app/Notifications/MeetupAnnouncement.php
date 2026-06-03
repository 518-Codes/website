<?php

namespace App\Notifications;

use App\Models\Meetup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetupAnnouncement extends Notification implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public readonly Meetup $meetup) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New meetup: {$this->meetup->title}")
            ->greeting('A new event has been announced!')
            ->line("**{$this->meetup->title}** is coming up.")
            ->line('Date: '.$this->meetup->starts_at->format('l, F j, Y \a\t g:ia'))
            ->line('Location: '.$this->meetup->location)
            ->action('View Event', route('events.show', $this->meetup->slug))
            ->line('Hope to see you there!');
    }
}
