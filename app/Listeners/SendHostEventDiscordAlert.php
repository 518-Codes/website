<?php

namespace App\Listeners;

use App\Events\HostEventSubmitted;
use App\Services\DiscordWebhook;
use Illuminate\Events\Attributes\HandleEvent;

#[HandleEvent(HostEventSubmitted::class)]
class SendHostEventDiscordAlert
{
    public function __construct(private readonly DiscordWebhook $discord) {}

    public function handle(HostEventSubmitted $event): void
    {
        $meetup = $event->meetup;
        $adminUrl = route('filament.admin.resources.meetups.edit', ['record' => $meetup->id]);

        $this->discord->sendEmbed('📋 New host event submission', [
            'title' => $meetup->title,
            'url' => $adminUrl,
            'color' => 0xFFB627,
            'fields' => [
                ['name' => 'Contact', 'value' => $meetup->contact_email ?? '—', 'inline' => true],
                ['name' => 'Proposed Date', 'value' => $meetup->starts_at->format('M j, Y'), 'inline' => true],
                ['name' => 'Location', 'value' => $meetup->location, 'inline' => false],
                ['name' => 'Description', 'value' => str($meetup->description)->limit(300), 'inline' => false],
            ],
            'footer' => ['text' => 'Review and publish in the admin panel'],
        ]);
    }
}
