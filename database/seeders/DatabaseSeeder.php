<?php

namespace Database\Seeders;

use App\Models\Meetup;
use App\Models\Tag;
use App\Models\User;
use Database\Factories\RsvpFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $tags = Tag::factory(10)->create();

        Meetup::factory(8)->create()->each(function (Meetup $meetup) use ($tags) {
            $meetup->tags()->attach($tags->random(rand(1, 4))->pluck('id'));
            $meetup->rsvps()->createMany(
                RsvpFactory::new()->count(rand(3, 12))->make()->toArray()
            );
        });
    }
}
