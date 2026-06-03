<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordWebhook
{
    public function send(string $message): void
    {
        $this->post(['content' => $message]);
    }

    /**
     * @param  array<string, mixed>  $embed
     */
    public function sendEmbed(string $content, array $embed): void
    {
        $this->post(['content' => $content, 'embeds' => [$embed]]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function post(array $payload): void
    {
        $url = config('services.discord.webhook_url');

        if (blank($url)) {
            return;
        }

        try {
            Http::post($url, $payload);
        } catch (\Throwable $e) {
            Log::warning('Discord webhook failed: '.$e->getMessage());
        }
    }
}
