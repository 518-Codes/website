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

it('duplicates a meetup to next week as a draft', function () {
    $tag = Tag::factory()->create();
    // Crosses the March 14, 2027 DST change: the copy must keep the 6pm wall-clock time.
    $meetup = Meetup::factory()->published()->create([
        'title' => 'Weekly Happy Hour',
        'slug' => 'weekly-happy-hour',
        'location' => 'The Olde English, Albany',
        'starts_at' => '2027-03-11 18:00:00',
        'ends_at' => '2027-03-11 21:00:00',
    ]);
    $meetup->tags()->attach($tag);
    $meetup->scheduleItems()->create(['time' => '6:00 PM', 'title' => 'Doors', 'order' => 1]);
    $meetup->images()->create(['path' => 'meetup-images/bar.jpg', 'alt' => 'The bar', 'order' => 0]);
    $meetup->rsvps()->create(['name' => 'Alice', 'email' => 'alice@example.com']);

    livewire(ListMeetups::class)
        ->callAction(TestAction::make('replicate')->table($meetup))
        ->assertHasNoErrors();

    $copy = Meetup::where('slug', 'weekly-happy-hour-2027-03-18')->firstOrFail();

    expect($copy->status)->toBe(MeetupStatus::Draft)
        ->and($copy->location)->toBe('The Olde English, Albany')
        ->and($copy->starts_at->format('Y-m-d H:i'))->toBe('2027-03-18 18:00')
        ->and($copy->ends_at->format('Y-m-d H:i'))->toBe('2027-03-18 21:00')
        ->and($copy->tags->pluck('id')->all())->toBe([$tag->id])
        ->and($copy->scheduleItems->pluck('title')->all())->toBe(['Doors'])
        ->and($copy->images->pluck('path')->all())->toBe(['meetup-images/bar.jpg'])
        ->and($copy->rsvps()->count())->toBe(0)
        ->and($meetup->fresh()->status)->toBe(MeetupStatus::Published);

    // Duplicating the same meetup again gets a distinct slug instead of failing.
    livewire(ListMeetups::class)
        ->callAction(TestAction::make('replicate')->table($meetup));

    expect(Meetup::where('slug', 'weekly-happy-hour-2027-03-18-2')->exists())->toBeTrue();
});
