<?php

use App\Enums\MeetupStatus;
use App\Filament\Resources\Meetups\Pages\CreateMeetup;
use App\Filament\Resources\Meetups\Pages\EditMeetup;
use App\Filament\Resources\Meetups\Pages\ListMeetups;
use App\Models\Meetup;
use App\Models\Tag;
use App\Models\User;
use Filament\Actions\Testing\TestAction;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('can list meetups', function () {
    $meetups = Meetup::factory(5)->create();

    livewire(ListMeetups::class)
        ->assertCanSeeTableRecords($meetups);
});

it('can create a meetup', function () {
    $tag = Tag::factory()->create();

    livewire(CreateMeetup::class)
        ->fillForm([
            'title' => 'Albany PHP Meetup',
            'slug' => 'albany-php-meetup',
            'description' => 'A great meetup for PHP developers.',
            'location' => '1 Washington Ave, Albany, NY',
            'starts_at' => '2026-06-01 18:00:00',
            'ends_at' => '2026-06-01 20:00:00',
            'status' => MeetupStatus::Published->value,
            'tags' => [$tag->id],
            'images' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    assertDatabaseHas(Meetup::class, [
        'title' => 'Albany PHP Meetup',
        'slug' => 'albany-php-meetup',
        'status' => 'published',
    ]);
});

it('requires title, location, and starts_at', function () {
    livewire(CreateMeetup::class)
        ->fillForm([
            'title' => null,
            'location' => null,
            'starts_at' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'title' => 'required',
            'location' => 'required',
            'starts_at' => 'required',
        ]);
});

it('can edit a meetup', function () {
    $meetup = Meetup::factory()->create();

    livewire(EditMeetup::class, ['record' => $meetup->id])
        ->fillForm(['title' => 'Updated Title', 'slug' => 'updated-title'])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Meetup::class, [
        'id' => $meetup->id,
        'title' => 'Updated Title',
    ]);
});

it('shows rsvp count in table', function () {
    $meetup = Meetup::factory()->create();
    $meetup->rsvps()->createMany([
        ['name' => 'Alice', 'email' => 'alice@example.com'],
        ['name' => 'Bob', 'email' => 'bob@example.com'],
    ]);

    livewire(ListMeetups::class)
        ->assertCanSeeTableRecords([$meetup]);
});

it('can delete a meetup', function () {
    $meetup = Meetup::factory()->create();

    livewire(ListMeetups::class)
        ->callAction(TestAction::make('delete')->table($meetup));

    expect(Meetup::find($meetup->id))->toBeNull();
});
