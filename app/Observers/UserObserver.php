<?php

namespace App\Observers;

use App\Models\User;
use App\Services\DiscordWebhook;

class UserObserver
{
    public function __construct(private readonly DiscordWebhook $discord) {}

    public function created(User $user): void
    {
        $this->discord->send("👤 New member: {$user->name} ({$user->email})");
    }
}
