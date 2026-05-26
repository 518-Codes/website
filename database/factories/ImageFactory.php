<?php

namespace Database\Factories;

use App\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'path' => 'images/'.fake()->uuid().'.jpg',
            'alt' => fake()->sentence(3),
            'order' => 0,
        ];
    }
}
