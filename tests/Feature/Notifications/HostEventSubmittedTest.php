<?php

use App\Livewire\HostEvent;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(fn () => Http::fake());

test('HostEvent sends Discord alert on submission', function () {
    config(['services.discord.webhook_url' => 'https://discord.test/webhook']);

    Livewire::test(HostEvent::class)
        ->set('title', 'My Meetup')
        ->set('location', 'Troy, NY')
        ->set('proposed_date', now()->addMonth()->toDateString())
        ->set('description', 'A great event.')
        ->set('contact_email', 'host@example.com')
        ->call('submit');

    Http::assertSent(fn ($request) => str_contains($request->body(), 'My Meetup') &&
        str_contains($request->body(), 'host@example.com')
    );
});
