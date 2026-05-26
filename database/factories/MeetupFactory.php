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
    public function definition(): array
    {
        $title = fake()->sentence(4);
        $startsAt = fake()->dateTimeBetween('now', '+6 months');

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->paragraphs(3, true),
            'location' => fake()->streetAddress().', '.fake()->city().', NY',
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
}
