<?php

namespace App\Livewire;

use App\Models\Meetup;
use Illuminate\Support\Collection;
use Livewire\Component;

class EventsIndex extends Component
{
    /**
     * Upcoming and in-progress meetups, grouped by month, soonest first.
     *
     * @return Collection<string, Collection<int, Meetup>>
     */
    public function meetupsByMonth(): Collection
    {
        return Meetup::published()
            ->upcoming()
            ->with(['tags', 'rsvps'])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Meetup $meetup) => $meetup->starts_at->format('F Y'));
    }

    /**
     * Meetups that have already happened, grouped by month, most recent first.
     *
     * @return Collection<string, Collection<int, Meetup>>
     */
    public function pastMeetupsByMonth(): Collection
    {
        return Meetup::published()
            ->past()
            ->with(['tags', 'rsvps'])
            ->orderByDesc('starts_at')
            ->get()
            ->groupBy(fn (Meetup $meetup) => $meetup->starts_at->format('F Y'));
    }

    public function render()
    {
        return view('livewire.events-index', [
            'meetupsByMonth' => $this->meetupsByMonth(),
            'pastMeetupsByMonth' => $this->pastMeetupsByMonth(),
        ])->layout('layouts.app', ['title' => 'events · 518.codes']);
    }
}
