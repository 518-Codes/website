<?php

use App\Filament\Resources\Rsvps\Pages\ListRsvps;
use App\Models\Meetup;
use App\Models\Rsvp;
use App\Models\User;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('can list rsvps', function () {
    $meetup = Meetup::factory()->create();
    $rsvps = collect([
        $meetup->rsvps()->create(['name' => 'Alice', 'email' => 'alice@example.com']),
        $meetup->rsvps()->create(['name' => 'Bob', 'email' => 'bob@example.com']),
    ]);

    livewire(ListRsvps::class)
        ->assertCanSeeTableRecords($rsvps);
});

it('can delete an rsvp', function () {
    $meetup = Meetup::factory()->create();
    $rsvp = $meetup->rsvps()->create(['name' => 'Alice', 'email' => 'alice@example.com']);

    livewire(ListRsvps::class)
        ->callAction(TestAction::make('delete')->table($rsvp));

    expect(Rsvp::find($rsvp->id))->toBeNull();
});

it('shows meetup title for each rsvp', function () {
    $meetup = Meetup::factory()->create(['title' => 'Test Meetup']);
    $meetup->rsvps()->create(['name' => 'Alice', 'email' => 'alice@example.com']);

    livewire(ListRsvps::class)
        ->assertSee('Test Meetup');
});
