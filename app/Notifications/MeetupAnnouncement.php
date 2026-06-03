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
        $timeRange = $this->meetup->ends_at
            ? $this->meetup->starts_at->format('l, F j, Y \a\t g:ia').' – '.$this->meetup->ends_at->format('g:ia')
            : $this->meetup->starts_at->format('l, F j, Y \a\t g:ia');

        $mapsUrl = $this->meetup->latitude && $this->meetup->longitude
            ? "https://www.google.com/maps?q={$this->meetup->latitude},{$this->meetup->longitude}"
            : null;

        $message = (new MailMessage)
            ->subject("New meetup: {$this->meetup->title}")
            ->greeting('A new meetup has been announced!')
            ->line("**{$this->meetup->title}**")
            ->line("**When:** {$timeRange}")
            ->line("**Where:** {$this->meetup->location}");

        if ($mapsUrl) {
            $message->line("[Get directions]({$mapsUrl})");
        }

        if ($this->meetup->what_to_expect) {
            $message->line("**What to expect:** {$this->meetup->what_to_expect}");
        }

        return $message
            ->action('RSVP Now', route('events.show', $this->meetup->slug))
            ->line('Hope to see you there!');
    }
}
