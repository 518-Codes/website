<?php

namespace App\Livewire;

use App\Enums\MeetupStatus;
use App\Events\HostEventSubmitted;
use App\Models\Meetup;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class HostEvent extends Component
{
    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string|max:255')]
    public string $location = '';

    #[Validate('required|date|after:today')]
    public string $proposed_date = '';

    #[Validate('required|string|max:2000')]
    public string $description = '';

    #[Validate('required|email|max:255')]
    public string $contact_email = '';

    public bool $submitted = false;

    public function submit(): void
    {
        $this->validate();

        $meetup = Meetup::create([
            'title' => $this->title,
            'slug' => Str::slug($this->title).'-'.Str::random(5),
            'description' => $this->description,
            'location' => $this->location,
            'starts_at' => $this->proposed_date,
            'status' => MeetupStatus::Draft,
            'contact_email' => $this->contact_email,
        ]);

        HostEventSubmitted::dispatch($meetup);

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.host-event')
            ->layout('layouts.app', ['title' => 'host an event · 518.codes']);
    }
}
