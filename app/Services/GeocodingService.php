<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class GeocodingService
{
    /**
     * Resolve a free-text location to coordinates via OSM Nominatim.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function geocode(string $location): ?array
    {
        $location = trim($location);

        if ($location === '') {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => '518codes-map/1.0 (https://518.codes)',
            ])->get('https://nominatim.openstreetmap.org/search', [
                'q' => $location,
                'format' => 'jsonv2',
                'limit' => 1,
            ]);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $first = $response->json(0);

        if (! is_array($first) || ! isset($first['lat'], $first['lon'])) {
            return null;
        }

        return [
            'lat' => (float) $first['lat'],
            'lng' => (float) $first['lon'],
        ];
    }
}
