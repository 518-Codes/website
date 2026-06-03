<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordWebhook
{
    public function send(string $message): void
    {
        $url = config('services.discord.webhook_url');

        if (blank($url)) {
            return;
        }

        try {
            Http::post($url, ['content' => $message]);
        } catch (\Throwable $e) {
            Log::warning('Discord webhook failed: '.$e->getMessage());
        }
    }
}
