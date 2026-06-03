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

        $timeRange = $this->meetup->ends_at
            ? $this->meetup->starts_at->format('l, F j, Y \a\t g:ia').' – '.$this->meetup->ends_at->format('g:ia')
            : $this->meetup->starts_at->format('l, F j, Y \a\t g:ia');

        $mapsUrl = $this->meetup->latitude && $this->meetup->longitude
            ? "https://www.google.com/maps?q={$this->meetup->latitude},{$this->meetup->longitude}"
            : null;

        $message = (new MailMessage)
            ->subject("Reminder: {$this->meetup->title} is {$when}")
            ->greeting("Don't forget — you've got a meetup {$when}!")
            ->line("**{$this->meetup->title}**")
            ->line("**When:** {$timeRange}")
            ->line("**Where:** {$this->meetup->location}");

        if ($mapsUrl) {
            $message->line("[Get directions]({$mapsUrl})");
        }

        return $message->action('View Event', route('events.show', $this->meetup->slug));
    }
}
