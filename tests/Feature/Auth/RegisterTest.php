<?php

use App\Livewire\Auth\Register;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

test('register page loads', function () {
    $this->get('/register')->assertOk();
});

test('user can register with valid data', function () {
    Livewire::test(Register::class)
        ->set('name', 'Ada Tang')
        ->set('username', 'adatang')
        ->set('email', 'ada@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertRedirect('/members/adatang/edit');

    assertDatabaseHas(User::class, [
        'email' => 'ada@example.com',
        'username' => 'adatang',
        'is_admin' => false,
    ]);
});

test('register requires all fields', function () {
    Livewire::test(Register::class)
        ->set('name', '')
        ->set('username', '')
        ->set('email', '')
        ->set('password', '')
        ->call('register')
        ->assertHasErrors(['name' => 'required', 'username' => 'required', 'email' => 'required', 'password' => 'required']);
});

test('register requires unique username', function () {
    User::factory()->create(['username' => 'taken']);

    Livewire::test(Register::class)
        ->set('name', 'Other Person')
        ->set('username', 'taken')
        ->set('email', 'other@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertHasErrors(['username' => 'unique']);
});

test('register requires unique email', function () {
    User::factory()->create(['email' => 'dupe@example.com']);

    Livewire::test(Register::class)
        ->set('name', 'Other Person')
        ->set('username', 'otherperson')
        ->set('email', 'dupe@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertHasErrors(['email' => 'unique']);
});

test('register requires password confirmation', function () {
    Livewire::test(Register::class)
        ->set('name', 'Ada Tang')
        ->set('username', 'adatang')
        ->set('email', 'ada@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'wrong')
        ->call('register')
        ->assertHasErrors(['password' => 'confirmed']);
});
