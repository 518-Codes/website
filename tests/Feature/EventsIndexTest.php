<?php

use App\Livewire\EventsIndex;
use App\Models\Meetup;

use function Pest\Livewire\livewire;

test('the events index lists upcoming events', function () {
    $meetup = Meetup::factory()->upcoming()->create(['title' => 'Future Hack Night']);

    livewire(EventsIndex::class)
        ->assertSee($meetup->title);
});

test('the events index keeps an in-progress event in the upcoming list', function () {
    $meetup = Meetup::factory()->published()->create([
        'title' => 'Happening Right Now',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);

    $component = livewire(EventsIndex::class);

    expect($component->viewData('meetupsByMonth')->flatten()->pluck('id'))
        ->toContain($meetup->id);

    $component->assertSee($meetup->title);
});

test('the events index shows past events in their own section', function () {
    $past = Meetup::factory()->published()->create([
        'title' => 'Archived Meetup',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->subMonth()->addHours(2),
    ]);

    $component = livewire(EventsIndex::class);

    expect($component->viewData('pastMeetupsByMonth')->flatten()->pluck('id'))
        ->toContain($past->id);

    $component->assertSee($past->title);
});

test('the events index does not list a past event as upcoming', function () {
    $past = Meetup::factory()->published()->create([
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->subMonth()->addHours(2),
    ]);

    expect(livewire(EventsIndex::class)->viewData('meetupsByMonth')->flatten()->pluck('id'))
        ->not->toContain($past->id);
});

test('the events index orders past events most recent first', function () {
    $older = Meetup::factory()->published()->create(['starts_at' => now()->subMonths(3), 'ends_at' => now()->subMonths(3)->addHours(2)]);
    $newer = Meetup::factory()->published()->create(['starts_at' => now()->subDays(2), 'ends_at' => now()->subDays(2)->addHours(2)]);

    $ids = livewire(EventsIndex::class)->viewData('pastMeetupsByMonth')->flatten()->pluck('id')->all();

    expect($ids)->toBe([$newer->id, $older->id]);
});

test('the events index excludes unpublished past events', function () {
    $draft = Meetup::factory()->draft()->create([
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->subMonth()->addHours(2),
    ]);

    expect(livewire(EventsIndex::class)->viewData('pastMeetupsByMonth')->flatten()->pluck('id'))
        ->not->toContain($draft->id);
});

test('the home page keeps an in-progress event as the featured meetup', function () {
    $meetup = Meetup::factory()->published()->create([
        'title' => 'Live Right Now Meetup',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee($meetup->title);
});

test('the home page drops an event once it has ended', function () {
    $meetup = Meetup::factory()->published()->create([
        'title' => 'Finished Meetup Title',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->subDay()->addHours(2),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee($meetup->title);
});

test('the home page labels an in-progress event as happening now', function () {
    Meetup::factory()->published()->create([
        'title' => 'Currently Running Meetup',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('HAPPENING NOW')
        ->assertDontSee('NEXT UP · 1 HOUR AGO');
});

test('the home page still counts down to an event that has not started', function () {
    Meetup::factory()->published()->create([
        'title' => 'Not Started Yet Meetup',
        'starts_at' => now()->addDays(3),
        'ends_at' => now()->addDays(3)->addHours(2),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('NEXT UP')
        ->assertDontSee('HAPPENING NOW');
});
