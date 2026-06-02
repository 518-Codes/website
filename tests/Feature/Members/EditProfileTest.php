<?php

use App\Livewire\Members\EditProfile;
use App\Models\Skill;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

test('edit profile page requires auth', function () {
    User::factory()->create(['username' => 'ada']);
    $this->get('/members/ada/edit')->assertRedirect('/login');
});

test('edit profile redirects non-owner', function () {
    User::factory()->create(['username' => 'ada']);
    $other = User::factory()->create(['username' => 'bob']);

    $this->actingAs($other)->get('/members/ada/edit')->assertForbidden();
});

test('owner can load edit profile page', function () {
    $user = User::factory()->create(['username' => 'ada']);
    $this->actingAs($user)->get('/members/ada/edit')->assertOk();
});

test('owner can update basic profile fields', function () {
    $user = User::factory()->create(['username' => 'ada']);

    Livewire::actingAs($user)
        ->test(EditProfile::class, ['username' => 'ada'])
        ->set('headline', 'Lead Engineer')
        ->set('bio', 'I build things.')
        ->set('company', 'Acme Corp')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/members/ada');

    assertDatabaseHas(User::class, [
        'id' => $user->id,
        'headline' => 'Lead Engineer',
        'bio' => 'I build things.',
        'company' => 'Acme Corp',
    ]);
});

test('owner can attach skills', function () {
    $user = User::factory()->create(['username' => 'ada']);
    $skill = Skill::factory()->create();

    Livewire::actingAs($user)
        ->test(EditProfile::class, ['username' => 'ada'])
        ->set('selectedSkillIds', [$skill->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($user->skills()->where('skill_id', $skill->id)->exists())->toBeTrue();
});

test('social urls must be valid urls', function () {
    $user = User::factory()->create(['username' => 'ada']);

    Livewire::actingAs($user)
        ->test(EditProfile::class, ['username' => 'ada'])
        ->set('githubUrl', 'not-a-url')
        ->call('save')
        ->assertHasErrors(['githubUrl' => 'url']);
});
