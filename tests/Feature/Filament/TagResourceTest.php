<?php

use App\Filament\Resources\Tags\Pages\CreateTag;
use App\Filament\Resources\Tags\Pages\EditTag;
use App\Filament\Resources\Tags\Pages\ListTags;
use App\Models\Tag;
use App\Models\User;
use Filament\Actions\Testing\TestAction;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('can list tags', function () {
    $tags = Tag::factory(5)->create();

    livewire(ListTags::class)
        ->assertCanSeeTableRecords($tags);
});

it('can create a tag', function () {
    livewire(CreateTag::class)
        ->fillForm([
            'name' => 'Laravel',
            'slug' => 'laravel',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    assertDatabaseHas(Tag::class, [
        'name' => 'Laravel',
        'slug' => 'laravel',
    ]);
});

it('auto-generates slug from name', function () {
    livewire(CreateTag::class)
        ->fillForm(['name' => 'My Cool Tag'])
        ->assertFormFieldIsVisible('slug');
});

it('requires name on create', function () {
    livewire(CreateTag::class)
        ->fillForm(['name' => null])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

it('can edit a tag', function () {
    $tag = Tag::factory()->create();

    livewire(EditTag::class, ['record' => $tag->id])
        ->fillForm(['name' => 'Updated', 'slug' => 'updated'])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Tag::class, [
        'id' => $tag->id,
        'name' => 'Updated',
        'slug' => 'updated',
    ]);
});

it('can delete a tag', function () {
    $tag = Tag::factory()->create();

    livewire(ListTags::class)
        ->callAction(TestAction::make('delete')->table($tag));

    expect(Tag::find($tag->id))->toBeNull();
});
