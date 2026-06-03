<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DiscordWebhook
{
    public function send(string $message): void
    {
        $url = config('services.discord.webhook_url');

        if (blank($url)) {
            return;
        }

        Http::post($url, ['content' => $message]);
    }
}
