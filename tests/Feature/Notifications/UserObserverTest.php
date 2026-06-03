<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => Http::fake());

test('UserObserver sends Discord alert when a user is created', function () {
    config(['services.discord.webhook_url' => 'https://discord.test/webhook']);

    User::factory()->create(['name' => 'Luigi B', 'email' => 'luigi@example.com']);

    Http::assertSent(fn ($request) => str_contains($request->body(), 'Luigi B') &&
        str_contains($request->body(), 'luigi@example.com')
    );
});
