<?php

use App\Livewire\EventDetail;
use App\Livewire\EventsIndex;
use App\Models\Meetup;
use App\Models\Rsvp;
use Illuminate\Support\Facades\Route;

Route::get('/events', EventsIndex::class);
Route::get('/events/{slug}', EventDetail::class);

Route::get('/', function () {
    $meetups = Meetup::published()
        ->upcoming()
        ->with(['tags', 'rsvps'])
        ->orderBy('starts_at')
        ->get();

    $nextMeetup = $meetups->first();
    $upcomingMeetups = $meetups->skip(1);

    $stats = [
        'events_hosted' => Meetup::published()->count(),
        'rsvps_total' => Rsvp::count(),
    ];

    return view('welcome', compact('nextMeetup', 'upcomingMeetups', 'stats'));
});
