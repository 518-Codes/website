<?php

namespace Database\Seeders;

use App\Models\Meetup;
use App\Models\MeetupScheduleItem;
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

        $defaultSchedule = [
            ['time' => '6:30 PM', 'title' => 'Doors open', 'note' => 'Come in, get settled, grab a drink.', 'order' => 0],
            ['time' => '7:00 PM', 'title' => 'Talks & demos', 'note' => 'Half-broken things especially welcome. No slides required.', 'order' => 1],
            ['time' => '8:00 PM', 'title' => 'Open discussion', 'note' => 'Drift between tables, ask questions, get unstuck.', 'order' => 2],
            ['time' => '9:00 PM', 'title' => 'Hallway track', 'note' => 'Continues wherever the group ends up.', 'order' => 3],
        ];

        // Upcoming published events (the majority).
        Meetup::factory(18)->upcoming()->create()->each(function (Meetup $meetup) use ($tags, $defaultSchedule) {
            $meetup->tags()->attach($tags->random(rand(1, 4))->pluck('id'));
            $meetup->rsvps()->createMany(
                RsvpFactory::new()->count(rand(3, 12))->make()->toArray()
            );
            $meetup->update([
                'what_to_expect' => '<p>'.implode('</p><p>', fake()->paragraphs(2)).'</p>',
            ]);
            foreach ($defaultSchedule as $item) {
                MeetupScheduleItem::create(['meetup_id' => $meetup->id] + $item);
            }
        });

        // A handful of past/draft/cancelled events for realistic variety.
        Meetup::factory(6)->create()->each(function (Meetup $meetup) use ($tags) {
            $meetup->tags()->attach($tags->random(rand(1, 3))->pluck('id'));
        });
    }
}
