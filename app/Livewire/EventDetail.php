<?php

namespace App\Livewire;

use App\Enums\MeetupStatus;
use App\Models\Meetup;
use Livewire\Attributes\Validate;
use Livewire\Component;

class EventDetail extends Component
{
    public Meetup $meetup;

    public bool $rsvpd = false;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    public function mount(string $slug): void
    {
        $this->meetup = Meetup::with(['tags', 'rsvps'])
            ->where('slug', $slug)
            ->where('status', MeetupStatus::Published)
            ->firstOrFail();
    }

    public function rsvp(): void
    {
        $this->validate();

        $this->meetup->rsvps()->create([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        $this->rsvpd = true;
        $this->meetup->load('rsvps');
    }

    public function render()
    {
        return view('livewire.event-detail')
            ->layout('layouts.app', [
                'title' => $this->meetup->title.' · 518.codes',
            ]);
    }
}
