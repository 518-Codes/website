<?php

namespace Database\Seeders;

use App\Enums\MeetupStatus;
use App\Models\Meetup;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Mock fixture for the map event-markers feature: one published meetup per day for
 * the next 31 days, scattered across the Albany->NYC corridor. Exercises every
 * threshold (spotlight <=2d, sparkle <=7d, label <=14d, beacon <=30d) and the
 * 30-day cutoff (the +31d event should not render).
 *
 * Re-running deletes the previous mock set first, so the layout is stable and
 * never duplicates. Positions are deterministic (golden-ratio / 1-over-pi spread)
 * so a given day always lands in the same place.
 *
 * A few days deliberately SHARE a venue so location-based grouping and the
 * "(+N more)" drill-in can be tested.
 */
class MockMapEventsSeeder extends Seeder
{
    use WithoutModelEvents;

    private const SLUG_PREFIX = 'mock-map-event-';

    /** Corridor bbox (mirrors scripts/region-sandbox/config.ts CORRIDOR). */
    private const MIN_LAT = 40.55;

    private const MAX_LAT = 43.2;

    private const MIN_LNG = -74.3;

    private const MAX_LNG = -73.4;

    /** Day offsets that share one Albany venue (tests grouping + "(+N more)"). */
    private const CLUSTER_DAYS = [2, 5, 20];

    private const CLUSTER_LOCATION = 'Washington Park, Albany, NY';

    private const CLUSTER_LAT = 42.6586;

    private const CLUSTER_LNG = -73.7704;

    public function run(): void
    {
        Meetup::query()->where('slug', 'like', self::SLUG_PREFIX.'%')->delete();

        $today = CarbonImmutable::today();

        for ($day = 1; $day <= 31; $day++) {
            $startsAt = $today->addDays($day)->setTime(18, 30);
            $inCluster = in_array($day, self::CLUSTER_DAYS, true);

            [$lat, $lng] = $inCluster
                ? [self::CLUSTER_LAT, self::CLUSTER_LNG]
                : $this->scatter($day);

            $location = $inCluster ? self::CLUSTER_LOCATION : "Corridor stop #{$day}, NY";

            Meetup::create([
                'title' => "Mock Meetup +{$day}d",
                'slug' => self::SLUG_PREFIX.$day,
                'description' => "Mock event {$day} day(s) out, starting {$startsAt->toDayDateTimeString()}. Seeded for map marker testing.",
                'location' => $location,
                'latitude' => $lat,
                'longitude' => $lng,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->addHours(2),
                'status' => MeetupStatus::Published->value,
            ]);
        }

        $this->command?->info('Seeded 31 mock map events (+1d..+31d), '.count(self::CLUSTER_DAYS).' sharing one venue for grouping.');
    }

    /**
     * Deterministic well-distributed point within the corridor bbox for a given day.
     * Golden-ratio and 1/pi step multipliers keep successive days far apart (sporadic)
     * while remaining stable across re-seeds.
     *
     * @return array{0: float, 1: float}
     */
    private function scatter(int $day): array
    {
        $fLat = fmod($day * 0.6180339887498949, 1.0);
        $fLng = fmod($day * 0.3183098861837907, 1.0);

        $lat = self::MIN_LAT + (self::MAX_LAT - self::MIN_LAT) * $fLat;
        $lng = self::MIN_LNG + (self::MAX_LNG - self::MIN_LNG) * $fLng;

        return [round($lat, 7), round($lng, 7)];
    }
}
