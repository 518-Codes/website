<?php

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

it('can list users', function () {
    $users = User::factory(3)->create();

    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users);
});
