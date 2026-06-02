<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Project> */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => ucwords(fake()->words(3, true)),
            'url' => fake()->optional()->url(),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
