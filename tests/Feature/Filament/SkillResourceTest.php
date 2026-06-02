<?php

use App\Filament\Resources\Skills\Pages\ListSkills;
use App\Models\Skill;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

it('can list skills', function () {
    $skills = Skill::factory(3)->create();

    livewire(ListSkills::class)
        ->assertCanSeeTableRecords($skills);
});
