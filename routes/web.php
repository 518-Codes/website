<?php

use App\Http\Controllers\IcsController;
use App\Http\Controllers\MapEventsController;
use App\Livewire\EventDetail;
use App\Livewire\EventsIndex;
use App\Livewire\HostEvent;
use App\Models\Meetup;
use App\Models\Rsvp;
use Illuminate\Support\Facades\Route;

Route::get('/api/map-events', MapEventsController::class);

Route::get('/map', fn () => view('region-map'))->name('map');

Route::get('/events', EventsIndex::class)->name('events.index');
Route::get('/events/{slug}', EventDetail::class)->name('events.show');
Route::get('/events/{slug}/ics', IcsController::class)->name('events.ics');
Route::get('/host', HostEvent::class)->name('host');

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
