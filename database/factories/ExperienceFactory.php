<?php

namespace Database\Factories;

use App\Models\Experience;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Experience> */
class ExperienceFactory extends Factory
{
    public function definition(): array
    {
        $startYear = fake()->numberBetween(2015, 2023);

        return [
            'user_id' => User::factory(),
            'title' => fake()->jobTitle(),
            'company' => fake()->company(),
            'start_year' => $startYear,
            'end_year' => fake()->boolean(70) ? fake()->numberBetween($startYear + 1, 2025) : null,
            'description' => fake()->optional()->sentence(),
        ];
    }
}
