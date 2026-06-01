<?php

use App\Filament\Resources\Meetups\Pages\CreateMeetup;
use App\Models\Meetup;
use App\Models\User;
use Illuminate\Support\Facades\Http;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('auto-geocodes a meetup on create when coordinates are blank', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([
            ['lat' => '42.6525793', 'lon' => '-73.7562317'],
        ]),
    ]);

    livewire(CreateMeetup::class)
        ->fillForm([
            'title' => 'Geocode Me',
            'slug' => 'geocode-me',
            'description' => 'desc',
            'location' => 'Albany, NY',
            'starts_at' => now()->addDays(5),
            'status' => 'published',
            'images' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $meetup = Meetup::where('slug', 'geocode-me')->first();
    expect($meetup->latitude)->toBe(42.6525793)
        ->and($meetup->longitude)->toBe(-73.7562317);
});

it('does not overwrite manually entered coordinates', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([
        ['lat' => '1.0', 'lon' => '2.0'],
    ])]);

    livewire(CreateMeetup::class)
        ->fillForm([
            'title' => 'Manual Coords',
            'slug' => 'manual-coords',
            'description' => 'desc',
            'location' => 'Albany, NY',
            'latitude' => 40.0,
            'longitude' => -73.5,
            'starts_at' => now()->addDays(5),
            'status' => 'published',
            'images' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $meetup = Meetup::where('slug', 'manual-coords')->first();
    expect($meetup->latitude)->toBe(40.0)
        ->and($meetup->longitude)->toBe(-73.5);

    Http::assertNothingSent();
});
