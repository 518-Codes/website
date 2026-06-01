<?php

namespace Database\Factories;

use App\Enums\MeetupStatus;
use App\Models\Meetup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Meetup>
 */
class MeetupFactory extends Factory
{
    /**
     * Real Albany-area venues with accurate coordinates.
     *
     * @var array<int, array{location: string, lat: float, lng: float}>
     */
    private const VENUES = [
        ['location' => 'Albany Capital Center, 55 Eagle St, Albany, NY', 'lat' => 42.6511, 'lng' => -73.7550],
        ['location' => 'Washington Park, Albany, NY', 'lat' => 42.6586, 'lng' => -73.7704],
        ['location' => 'The Hollow Bar + Kitchen, 79 N Pearl St, Albany, NY', 'lat' => 42.6527, 'lng' => -73.7520],
        ['location' => 'Proctors Theatre, 432 State St, Schenectady, NY', 'lat' => 42.8145, 'lng' => -73.9397],
        ['location' => 'Troy Music Hall, 30 2nd St, Troy, NY', 'lat' => 42.7284, 'lng' => -73.6918],
        ['location' => 'miSci Museum, 15 Nott Terrace, Schenectady, NY', 'lat' => 42.8175, 'lng' => -73.9375],
        ['location' => 'The Egg, Empire State Plaza, Albany, NY', 'lat' => 42.6536, 'lng' => -73.7566],
        ['location' => 'Saratoga Springs City Center, 522 Broadway, Saratoga Springs, NY', 'lat' => 43.0831, 'lng' => -73.7846],
        ['location' => 'Universal Preservation Hall, 25 Washington St, Saratoga Springs, NY', 'lat' => 43.0817, 'lng' => -73.7839],
        ['location' => 'Hudson Valley Community College, Troy, NY', 'lat' => 42.7037, 'lng' => -73.7005],
        ['location' => 'The Linda, 339 Central Ave, Albany, NY', 'lat' => 42.6601, 'lng' => -73.7858],
        ['location' => 'Cohoes Music Hall, 58 Remsen St, Cohoes, NY', 'lat' => 42.7759, 'lng' => -73.7003],
        ['location' => 'Collar City CrossFit, 274 River St, Troy, NY', 'lat' => 42.7356, 'lng' => -73.6873],
        ['location' => 'Guilderland Public Library, 2228 Western Ave, Guilderland, NY', 'lat' => 42.6918, 'lng' => -73.9067],
        ['location' => 'Bethlehem Public Library, 451 Delaware Ave, Delmar, NY', 'lat' => 42.6244, 'lng' => -73.8303],
        ['location' => 'Verdile\'s Restaurant, 669 Broadway, Menands, NY', 'lat' => 42.6903, 'lng' => -73.7300],
        ['location' => 'Albany Pump Station, 19 Quackenbush Square, Albany, NY', 'lat' => 42.6571, 'lng' => -73.7499],
        ['location' => 'Clifton Park Center Mall, 22 Clifton Country Rd, Clifton Park, NY', 'lat' => 42.8609, 'lng' => -73.8339],
        ['location' => 'Glens Falls Civic Center, 1 Civic Center Plaza, Glens Falls, NY', 'lat' => 43.3095, 'lng' => -73.6440],
        ['location' => 'Watervliet Arsenal Museum, 1 Buffington St, Watervliet, NY', 'lat' => 42.7297, 'lng' => -73.7104],
    ];

    public function definition(): array
    {
        $title = fake()->sentence(4);
        $startsAt = fake()->dateTimeBetween('now', '+6 months');
        $venue = fake()->randomElement(self::VENUES);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->paragraphs(3, true),
            'location' => $venue['location'],
            'latitude' => $venue['lat'],
            'longitude' => $venue['lng'],
            'starts_at' => $startsAt,
            'ends_at' => fake()->dateTimeBetween($startsAt, (clone $startsAt)->modify('+3 hours')),
            'status' => fake()->randomElement(MeetupStatus::cases())->value,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => MeetupStatus::Draft->value]);
    }

    public function published(): static
    {
        return $this->state(['status' => MeetupStatus::Published->value]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => MeetupStatus::Cancelled->value]);
    }

    public function upcoming(): static
    {
        return $this->state(function () {
            $startsAt = fake()->dateTimeBetween('+1 day', '+6 months');

            return [
                'starts_at' => $startsAt,
                'ends_at' => fake()->dateTimeBetween($startsAt, (clone $startsAt)->modify('+3 hours')),
                'status' => MeetupStatus::Published->value,
            ];
        });
    }
}
