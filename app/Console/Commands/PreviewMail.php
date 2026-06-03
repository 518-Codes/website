<?php

namespace App\Console\Commands;

use App\Enums\MeetupStatus;
use App\Models\Meetup;
use App\Models\Rsvp;
use App\Models\User;
use App\Notifications\MeetupAnnouncement;
use App\Notifications\MeetupReminder;
use App\Notifications\RsvpConfirmation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

#[Signature('mail:preview {type=all : rsvp-confirmation|meetup-announcement|meetup-reminder-24h|meetup-reminder-1h|all}')]
#[Description('Send preview emails for one or all notification types')]
class PreviewMail extends Command
{
    public function handle(): int
    {
        $type = $this->argument('type');

        $types = $type === 'all'
            ? ['rsvp-confirmation', 'meetup-announcement', 'meetup-reminder-24h', 'meetup-reminder-1h']
            : [$type];

        $valid = ['rsvp-confirmation', 'meetup-announcement', 'meetup-reminder-24h', 'meetup-reminder-1h', 'all'];
        if (! in_array($type, $valid)) {
            $this->error("Invalid type [{$type}]. Valid options: all, rsvp-confirmation, meetup-announcement, meetup-reminder-24h, meetup-reminder-1h");

            return self::FAILURE;
        }

        $recipient = $this->resolveRecipient();
        $meetup = $this->resolveMeetup();

        foreach ($types as $t) {
            $this->sendPreview($t, $recipient, $meetup);
        }

        $this->info('Preview email(s) sent to: '.$recipient->email);

        return self::SUCCESS;
    }

    private function sendPreview(string $type, User $recipient, Meetup $meetup): void
    {
        match ($type) {
            'rsvp-confirmation' => $this->sendRsvpConfirmation($recipient, $meetup),
            'meetup-announcement' => $recipient->notifyNow(new MeetupAnnouncement($meetup)),
            'meetup-reminder-24h' => $recipient->notifyNow(new MeetupReminder($meetup, '24h')),
            'meetup-reminder-1h' => $recipient->notifyNow(new MeetupReminder($meetup, '1h')),
        };

        $this->line("  <fg=green>✓</> {$type}");
    }

    private function sendRsvpConfirmation(User $recipient, Meetup $meetup): void
    {
        $rsvp = Rsvp::factory()->make([
            'name' => $recipient->name,
            'email' => $recipient->email,
        ]);
        $rsvp->setRelation('meetup', $meetup);

        Notification::route('mail', $recipient->email)
            ->notifyNow(new RsvpConfirmation($rsvp));
    }

    private function resolveRecipient(): User
    {
        return User::first() ?? User::factory()->make([
            'name' => config('app.name').' Preview',
            'email' => config('mail.from.address'),
        ]);
    }

    private function resolveMeetup(): Meetup
    {
        return Meetup::first() ?? Meetup::factory()->make([
            'title' => 'Capital Region Dev Meetup — June 2026',
            'slug' => 'capital-region-dev-meetup-june-2026',
            'location' => 'Albany Capital Center, 55 Eagle St, Albany, NY',
            'starts_at' => now()->addDay()->setTime(18, 30),
            'ends_at' => now()->addDay()->setTime(21, 0),
            'status' => MeetupStatus::Published,
        ]);
    }
}
