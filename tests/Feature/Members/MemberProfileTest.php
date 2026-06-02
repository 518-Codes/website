<?php

use App\Livewire\Members\MemberProfile;
use App\Models\Experience;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use Livewire\Livewire;

test('public profile page loads', function () {
    User::factory()->create(['username' => 'ada']);
    $this->get('/members/ada')->assertOk();
});

test('profile shows user name and headline', function () {
    $user = User::factory()->create([
        'username' => 'ada',
        'headline' => 'Senior Engineer',
    ]);

    Livewire::test(MemberProfile::class, ['username' => 'ada'])
        ->assertSee($user->name)
        ->assertSee('Senior Engineer');
});

test('profile shows skills', function () {
    $user = User::factory()->create(['username' => 'ada']);
    $skill = Skill::factory()->create(['name' => 'Laravel']);
    $user->skills()->attach($skill);

    Livewire::test(MemberProfile::class, ['username' => 'ada'])
        ->assertSee('Laravel');
});

test('profile shows experiences', function () {
    $user = User::factory()->create(['username' => 'ada']);
    Experience::factory()->create(['user_id' => $user->id, 'title' => 'Lead Dev', 'company' => 'Acme']);

    Livewire::test(MemberProfile::class, ['username' => 'ada'])
        ->assertSee('Lead Dev')
        ->assertSee('Acme');
});

test('profile shows projects', function () {
    $user = User::factory()->create(['username' => 'ada']);
    Project::factory()->create(['user_id' => $user->id, 'title' => 'My App']);

    Livewire::test(MemberProfile::class, ['username' => 'ada'])
        ->assertSee('My App');
});

test('profile returns 404 for unknown username', function () {
    $this->get('/members/nobody')->assertNotFound();
});

test('edit button visible to owner', function () {
    $user = User::factory()->create(['username' => 'ada']);

    Livewire::actingAs($user)
        ->test(MemberProfile::class, ['username' => 'ada'])
        ->assertSee('edit profile');
});

test('edit button not visible to guests', function () {
    User::factory()->create(['username' => 'ada']);

    Livewire::test(MemberProfile::class, ['username' => 'ada'])
        ->assertDontSee('edit profile');
});
