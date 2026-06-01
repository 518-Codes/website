<?php

use App\Services\GeocodingService;
use Illuminate\Support\Facades\Http;

it('returns lat/lng for a resolvable location', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([
            ['lat' => '42.6525793', 'lon' => '-73.7562317'],
        ]),
    ]);

    $coords = app(GeocodingService::class)->geocode('Albany, NY');

    expect($coords)->toBe(['lat' => 42.6525793, 'lng' => -73.7562317]);
});

it('returns null when nothing matches', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([])]);

    expect(app(GeocodingService::class)->geocode('nowhere at all'))->toBeNull();
});

it('returns null on a failed request', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response('', 500)]);

    expect(app(GeocodingService::class)->geocode('Albany, NY'))->toBeNull();
});
