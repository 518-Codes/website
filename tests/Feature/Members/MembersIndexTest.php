<?php

use App\Livewire\Members\MembersIndex;
use App\Models\User;
use Livewire\Livewire;

test('members index page loads', function () {
    $this->get('/members')->assertOk();
});

test('members index shows users with usernames', function () {
    $users = User::factory(3)->create();

    Livewire::test(MembersIndex::class)
        ->assertSee($users[0]->name)
        ->assertSee($users[1]->name)
        ->assertSee($users[2]->name);
});

test('members index can be searched by name', function () {
    User::factory()->create(['name' => 'Ada Tang', 'username' => 'ada']);
    User::factory()->create(['name' => 'Bob Smith', 'username' => 'bob']);

    Livewire::test(MembersIndex::class)
        ->set('search', 'Ada')
        ->assertSee('Ada Tang')
        ->assertDontSee('Bob Smith');
});
