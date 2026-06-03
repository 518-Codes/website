<?php

namespace App\Notifications;

use App\Models\Meetup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetupReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public readonly Meetup $meetup,
        public readonly string $timing,
    ) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $when = $this->timing === '24h' ? 'tomorrow' : 'in one hour';

        return (new MailMessage)
            ->subject("Reminder: {$this->meetup->title} is {$when}")
            ->greeting("Don't forget!")
            ->line("**{$this->meetup->title}** is happening {$when}.")
            ->line('Date: '.$this->meetup->starts_at->format('l, F j, Y \a\t g:ia'))
            ->line('Location: '.$this->meetup->location)
            ->action('View Event', route('events.show', $this->meetup->slug));
    }
}
