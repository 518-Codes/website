<?php

namespace App\Listeners;

use App\Events\HostEventSubmitted;
use App\Services\DiscordWebhook;

class SendHostEventDiscordAlert
{
    public function __construct(private readonly DiscordWebhook $discord) {}

    public function handle(HostEventSubmitted $event): void
    {
        $this->discord->send(
            "📋 New host submission from {$event->name} ({$event->email})"
        );
    }
}
