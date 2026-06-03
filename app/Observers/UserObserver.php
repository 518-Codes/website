<?php

namespace App\Observers;

use App\Models\User;
use App\Services\DiscordWebhook;

class UserObserver
{
    public function __construct(private readonly DiscordWebhook $discord) {}

    public function created(User $user): void
    {
        $adminUrl = route('filament.admin.resources.users.edit', ['record' => $user->id]);

        $this->discord->sendEmbed('👤 New member registered', [
            'title' => $user->name,
            'url' => $adminUrl,
            'color' => 0x5EFC8D,
            'fields' => [
                ['name' => 'Email', 'value' => $user->email, 'inline' => true],
                ['name' => 'Username', 'value' => "@{$user->username}", 'inline' => true],
                ['name' => 'Joined', 'value' => $user->created_at->format('M j, Y g:ia'), 'inline' => false],
            ],
        ]);
    }
}
