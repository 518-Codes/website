<?php

use App\Livewire\Auth\Login;
use App\Models\User;
use Livewire\Livewire;

test('login page loads', function () {
    $this->get('/login')->assertOk();
});

test('user can log in with valid credentials', function () {
    $user = User::factory()->create(['email' => 'ada@example.com']);

    Livewire::test(Login::class)
        ->set('email', 'ada@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect('/');

    expect(auth()->check())->toBeTrue();
});

test('login fails with wrong password', function () {
    User::factory()->create(['email' => 'ada@example.com']);

    Livewire::test(Login::class)
        ->set('email', 'ada@example.com')
        ->set('password', 'wrong')
        ->call('login')
        ->assertHasErrors(['email']);
});

test('login requires email and password', function () {
    Livewire::test(Login::class)
        ->set('email', '')
        ->set('password', '')
        ->call('login')
        ->assertHasErrors(['email' => 'required', 'password' => 'required']);
});

test('authenticated user is redirected away from login', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/login')->assertRedirect('/');
});
