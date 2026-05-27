<?php

namespace App\Livewire;

use App\Models\Meetup;
use Illuminate\Support\Collection;
use Livewire\Component;

class EventsIndex extends Component
{
    public function meetupsByMonth(): Collection
    {
        return Meetup::published()
            ->upcoming()
            ->with(['tags', 'rsvps'])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Meetup $meetup) => $meetup->starts_at->format('F Y'));
    }

    public function render()
    {
        return view('livewire.events-index', [
            'meetupsByMonth' => $this->meetupsByMonth(),
        ])->layout('layouts.app', ['title' => 'events · 518.codes']);
    }
}
