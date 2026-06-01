<?php

use App\Enums\MeetupStatus;
use App\Livewire\HostEvent;
use App\Models\Meetup;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

it('renders the host page', function () {
    $this->get(route('host'))->assertOk();
});

it('submits a proposal as a draft', function () {
    livewire(HostEvent::class)
        ->set('title', 'Albany Rust Night')
        ->set('location', 'Troy Public Library, 100 2nd St, Troy, NY')
        ->set('proposed_date', now()->addMonth()->format('Y-m-d\TH:i'))
        ->set('description', 'A casual evening about Rust and systems programming.')
        ->set('contact_email', 'organiser@example.com')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    assertDatabaseHas(Meetup::class, [
        'title' => 'Albany Rust Night',
        'status' => MeetupStatus::Draft->value,
        'contact_email' => 'organiser@example.com',
    ]);
});

it('validates required fields', function () {
    livewire(HostEvent::class)
        ->call('submit')
        ->assertHasErrors(['title', 'location', 'proposed_date', 'description', 'contact_email']);
});
