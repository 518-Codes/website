<?php

use App\Http\Controllers\IcsController;
use App\Http\Controllers\MapEventsController;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\EventDetail;
use App\Livewire\EventsIndex;
use App\Livewire\HostEvent;
use App\Livewire\Members\EditProfile;
use App\Livewire\Members\MemberProfile;
use App\Livewire\Members\MembersIndex;
use App\Models\Meetup;
use App\Models\Rsvp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/register', Register::class)->name('register');
Route::get('/login', Login::class)->name('login');
Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect('/');
})->name('logout')->middleware('auth');

Route::get('/members', MembersIndex::class)->name('members.index');
Route::get('/members/{username}', MemberProfile::class)->name('members.show');
Route::get('/members/{username}/edit', EditProfile::class)->name('members.edit')->middleware('auth');

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
