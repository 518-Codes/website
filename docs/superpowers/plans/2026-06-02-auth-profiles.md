# Auth + Member Profiles Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add user authentication and public member profiles, including an optional RSVP → account-creation flow and a roster that links to profiles.

**Architecture:** Auth uses Laravel's built-in session/guard system with hand-rolled Livewire components (no Breeze scaffolding) to match the existing terminal aesthetic. Profiles are public Livewire pages at `/members/{username}` and `/members`. The RSVP flow gains a post-RSVP password prompt that optionally creates an account inline. Filament gains `is_admin` gating, a Users resource, and a Skills resource.

**Tech Stack:** Laravel 13, Livewire 4, Filament 5, Pest 4, TailwindCSS v4 (custom CSS vars), PHP 8.4

---

## File Map

### New migrations
- `database/migrations/..._add_profile_fields_to_users_table.php` — username, is_admin, bio, headline, company, avatar_path, social URLs
- `database/migrations/..._create_skills_table.php` — id, name, slug, timestamps
- `database/migrations/..._create_skill_user_table.php` — pivot
- `database/migrations/..._create_experiences_table.php`
- `database/migrations/..._create_projects_table.php`
- `database/migrations/..._add_user_id_to_rsvps_table.php`

### Modified models
- `app/Models/User.php` — add fillable fields, relationships (skills, experiences, projects, rsvps), `is_admin` cast, `canAccessPanel` gate
- `app/Models/Rsvp.php` — add `user_id` fillable + `user()` BelongsTo

### New models
- `app/Models/Skill.php` — id, name, slug; BelongsToMany users
- `app/Models/Experience.php` — BelongsTo user
- `app/Models/Project.php` — BelongsTo user

### New factories
- `database/factories/SkillFactory.php`
- `database/factories/ExperienceFactory.php`
- `database/factories/ProjectFactory.php`

### Modified seeder
- `database/seeders/TeamSeeder.php` — set `is_admin = true` on existing team members, add usernames

### New Livewire components
- `app/Livewire/Auth/Register.php` + `resources/views/livewire/auth/register.blade.php`
- `app/Livewire/Auth/Login.php` + `resources/views/livewire/auth/login.blade.php`
- `app/Livewire/Members/MembersIndex.php` + `resources/views/livewire/members/members-index.blade.php`
- `app/Livewire/Members/MemberProfile.php` + `resources/views/livewire/members/member-profile.blade.php`
- `app/Livewire/Members/EditProfile.php` + `resources/views/livewire/members/edit-profile.blade.php`

### Modified Livewire components
- `app/Livewire/EventDetail.php` — add post-RSVP password prompt logic
- `resources/views/livewire/event-detail.blade.php` — add password prompt UI + linked roster names

### Modified layout
- `resources/views/layouts/app.blade.php` — add login/logout nav links

### New routes
- `routes/web.php` — register, login, logout, members index, member profile, edit profile

### New Filament resources
- `app/Filament/Resources/Users/UserResource.php` + Pages + Schema + Table
- `app/Filament/Resources/Skills/SkillResource.php` + Pages + Schema + Table

### Modified Filament
- `app/Filament/Resources/Meetups/RelationManagers/RsvpsRelationManager.php` — show user link if present
- `app/Providers/Filament/AdminPanelProvider.php` — register new resources

### New tests
- `tests/Feature/Auth/RegisterTest.php`
- `tests/Feature/Auth/LoginTest.php`
- `tests/Feature/Members/MemberProfileTest.php`
- `tests/Feature/Members/EditProfileTest.php`
- `tests/Feature/Members/MembersIndexTest.php`
- `tests/Feature/EventDetailRsvpAccountTest.php`
- `tests/Feature/Filament/UserResourceTest.php`
- `tests/Feature/Filament/SkillResourceTest.php`

---

## Task 1: Migrations — users profile fields + is_admin

**Files:**
- Create: `database/migrations/2026_06_02_100000_add_profile_fields_to_users_table.php`

- [ ] **Step 1: Generate the migration**

```bash
php artisan make:migration add_profile_fields_to_users_table --no-interaction
```

- [ ] **Step 2: Write the migration**

Replace the generated `up()` and `down()` with:

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('username')->unique()->nullable()->after('name');
        $table->boolean('is_admin')->default(false)->after('username');
        $table->string('headline')->nullable()->after('is_admin');
        $table->text('bio')->nullable()->after('headline');
        $table->string('company')->nullable()->after('bio');
        $table->string('avatar_path')->nullable()->after('company');
        $table->string('website_url')->nullable()->after('avatar_path');
        $table->string('github_url')->nullable()->after('website_url');
        $table->string('twitter_url')->nullable()->after('github_url');
        $table->string('linkedin_url')->nullable()->after('twitter_url');
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn([
            'username', 'is_admin', 'headline', 'bio', 'company',
            'avatar_path', 'website_url', 'github_url', 'twitter_url', 'linkedin_url',
        ]);
    });
}
```

- [ ] **Step 3: Run the migration**

```bash
php artisan migrate --no-interaction
```

Expected: `Migrating: ..._add_profile_fields_to_users_table` then `Migrated`.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/
git commit -m "feat: add profile fields and is_admin to users table"
```

---

## Task 2: Migrations — skills, experiences, projects, rsvp user_id

**Files:**
- Create: `database/migrations/2026_06_02_100001_create_skills_table.php`
- Create: `database/migrations/2026_06_02_100002_create_skill_user_table.php`
- Create: `database/migrations/2026_06_02_100003_create_experiences_table.php`
- Create: `database/migrations/2026_06_02_100004_create_projects_table.php`
- Create: `database/migrations/2026_06_02_100005_add_user_id_to_rsvps_table.php`

- [ ] **Step 1: Generate all five migrations**

```bash
php artisan make:migration create_skills_table --no-interaction
php artisan make:migration create_skill_user_table --no-interaction
php artisan make:migration create_experiences_table --no-interaction
php artisan make:migration create_projects_table --no-interaction
php artisan make:migration add_user_id_to_rsvps_table --no-interaction
```

- [ ] **Step 2: Write the skills migration**

```php
public function up(): void
{
    Schema::create('skills', function (Blueprint $table) {
        $table->id();
        $table->string('name')->unique();
        $table->string('slug')->unique();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('skills');
}
```

- [ ] **Step 3: Write the skill_user pivot migration**

```php
public function up(): void
{
    Schema::create('skill_user', function (Blueprint $table) {
        $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->primary(['skill_id', 'user_id']);
    });
}

public function down(): void
{
    Schema::dropIfExists('skill_user');
}
```

- [ ] **Step 4: Write the experiences migration**

```php
public function up(): void
{
    Schema::create('experiences', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('title');
        $table->string('company');
        $table->unsignedSmallInteger('start_year');
        $table->unsignedSmallInteger('end_year')->nullable();
        $table->text('description')->nullable();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('experiences');
}
```

- [ ] **Step 5: Write the projects migration**

```php
public function up(): void
{
    Schema::create('projects', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('title');
        $table->string('url')->nullable();
        $table->text('description')->nullable();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('projects');
}
```

- [ ] **Step 6: Write the rsvps user_id migration**

```php
public function up(): void
{
    Schema::table('rsvps', function (Blueprint $table) {
        $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->after('meetup_id');
    });
}

public function down(): void
{
    Schema::table('rsvps', function (Blueprint $table) {
        $table->dropConstrainedForeignId('user_id');
    });
}
```

- [ ] **Step 7: Run all migrations**

```bash
php artisan migrate --no-interaction
```

Expected: five new `Migrated:` lines.

- [ ] **Step 8: Commit**

```bash
git add database/migrations/
git commit -m "feat: add skills, experiences, projects tables and rsvp user_id"
```

---

## Task 3: Models — Skill, Experience, Project

**Files:**
- Create: `app/Models/Skill.php`
- Create: `app/Models/Experience.php`
- Create: `app/Models/Project.php`
- Create: `database/factories/SkillFactory.php`
- Create: `database/factories/ExperienceFactory.php`
- Create: `database/factories/ProjectFactory.php`

- [ ] **Step 1: Generate models with factories**

```bash
php artisan make:model Skill --factory --no-interaction
php artisan make:model Experience --factory --no-interaction
php artisan make:model Project --factory --no-interaction
```

- [ ] **Step 2: Write Skill model**

Replace the generated `app/Models/Skill.php`:

```php
<?php

namespace App\Models;

use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug'])]
class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use HasFactory;

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
```

- [ ] **Step 3: Write Experience model**

Replace the generated `app/Models/Experience.php`:

```php
<?php

namespace App\Models;

use Database\Factories\ExperienceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'title', 'company', 'start_year', 'end_year', 'description'])]
class Experience extends Model
{
    /** @use HasFactory<ExperienceFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 4: Write Project model**

Replace the generated `app/Models/Project.php`:

```php
<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'title', 'url', 'description'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 5: Write SkillFactory**

```php
<?php

namespace Database\Factories;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Skill> */
class SkillFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
        ];
    }
}
```

- [ ] **Step 6: Write ExperienceFactory**

```php
<?php

namespace Database\Factories;

use App\Models\Experience;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Experience> */
class ExperienceFactory extends Factory
{
    public function definition(): array
    {
        $startYear = fake()->numberBetween(2015, 2023);

        return [
            'user_id' => User::factory(),
            'title' => fake()->jobTitle(),
            'company' => fake()->company(),
            'start_year' => $startYear,
            'end_year' => fake()->boolean(70) ? fake()->numberBetween($startYear + 1, 2025) : null,
            'description' => fake()->optional()->sentence(),
        ];
    }
}
```

- [ ] **Step 7: Write ProjectFactory**

```php
<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Project> */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => ucwords(fake()->words(3, true)),
            'url' => fake()->optional()->url(),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
```

- [ ] **Step 8: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 9: Commit**

```bash
git add app/Models/ database/factories/
git commit -m "feat: add Skill, Experience, Project models and factories"
```

---

## Task 4: Update User and Rsvp models

**Files:**
- Modify: `app/Models/User.php`
- Modify: `app/Models/Rsvp.php`
- Modify: `database/factories/UserFactory.php`

- [ ] **Step 1: Rewrite User model**

```php
<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'username', 'headline', 'bio', 'company', 'avatar_path', 'website_url', 'github_url', 'twitter_url', 'linkedin_url'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class)->orderByDesc('start_year');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(Rsvp::class);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }
}
```

- [ ] **Step 2: Update Rsvp model**

```php
<?php

namespace App\Models;

use Database\Factories\RsvpFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['meetup_id', 'user_id', 'name', 'email'])]
class Rsvp extends Model
{
    /** @use HasFactory<RsvpFactory> */
    use HasFactory;

    public function meetup(): BelongsTo
    {
        return $this->belongsTo(Meetup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 3: Update UserFactory to include username and is_admin**

Add `username` and `is_admin` to the `definition()` array, and add an `admin()` state:

```php
public function definition(): array
{
    $name = fake()->name();

    return [
        'name' => $name,
        'username' => \Illuminate\Support\Str::slug($name) . fake()->unique()->numberBetween(1, 9999),
        'email' => fake()->unique()->safeEmail(),
        'email_verified_at' => now(),
        'password' => static::$password ??= \Illuminate\Support\Facades\Hash::make('password'),
        'remember_token' => \Illuminate\Support\Str::random(10),
        'is_admin' => false,
    ];
}

public function admin(): static
{
    return $this->state(fn (array $attributes) => ['is_admin' => true]);
}

public function unverified(): static
{
    return $this->state(fn (array $attributes) => [
        'email_verified_at' => null,
    ]);
}
```

- [ ] **Step 4: Update TeamSeeder to set is_admin and usernames**

```php
public function run(): void
{
    $members = [
        ['name' => 'Luigi Battaglioli', 'email' => 'him@theluigi.com', 'username' => 'luigi'],
        ['name' => 'Garret Premo', 'email' => 'garret@518.codes', 'username' => 'garret'],
        ['name' => 'Frank Matranga', 'email' => 'frank@518.codes', 'username' => 'frank'],
    ];

    foreach ($members as $member) {
        User::firstOrCreate(
            ['email' => $member['email']],
            [
                'name' => $member['name'],
                'username' => $member['username'],
                'password' => Hash::make('password'),
                'is_admin' => true,
            ],
        )->update(['is_admin' => true, 'username' => $member['username']]);
    }
}
```

- [ ] **Step 5: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add app/Models/ database/factories/ database/seeders/
git commit -m "feat: update User and Rsvp models with profile fields and relationships"
```

---

## Task 5: Auth routes and Livewire components (Register + Login)

**Files:**
- Create: `app/Livewire/Auth/Register.php`
- Create: `resources/views/livewire/auth/register.blade.php`
- Create: `app/Livewire/Auth/Login.php`
- Create: `resources/views/livewire/auth/login.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/app.blade.php`

- [ ] **Step 1: Write failing register test first**

```bash
php artisan make:test --pest Auth/RegisterTest --no-interaction
```

Write `tests/Feature/Auth/RegisterTest.php`:

```php
<?php

use App\Models\User;
use App\Livewire\Auth\Register;
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
```

- [ ] **Step 2: Run register tests to confirm they fail**

```bash
php artisan test --compact --filter=RegisterTest
```

Expected: FAIL (class not found).

- [ ] **Step 3: Create Register Livewire component**

```bash
php artisan make:livewire Auth/Register --no-interaction
```

Write `app/Livewire/Auth/Register.php`:

```php
<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Register extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255|unique:users,username|alpha_dash')]
    public string $username = '';

    #[Validate('required|email|max:255|unique:users,email')]
    public string $email = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    public function register(): void
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        Auth::login($user);

        $this->redirect("/members/{$this->username}/edit", navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register')
            ->layout('layouts.app', ['title' => 'register · 518.codes']);
    }
}
```

- [ ] **Step 4: Write register view**

Create `resources/views/livewire/auth/register.blade.php`:

```blade
<div>
    <style>
        .auth-page { max-width: 480px; margin: 80px auto; padding: 0 32px; }
        .auth-box { border: 2px solid var(--fg); box-shadow: var(--shadow-2); }
        .auth-head {
            background: var(--fg); color: var(--bg); padding: 8px 14px;
            font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; font-weight: 700;
        }
        .auth-body { padding: 24px; display: flex; flex-direction: column; gap: 14px; }
        .auth-field { display: flex; flex-direction: column; gap: 4px; }
        .auth-label { font-size: 11px; letter-spacing: 0.08em; text-transform: uppercase; color: var(--fg-mute); }
        .auth-input {
            padding: 8px 12px; border: 2px solid var(--fg); background: var(--bg);
            color: var(--fg); font-family: var(--font-mono); font-size: 13px; outline: none;
        }
        .auth-input:focus { border-color: var(--accent); }
        .auth-error { font-size: 11px; color: var(--amber); margin-top: 2px; }
        .auth-footer { font-size: 13px; color: var(--fg-dim); text-align: center; padding: 14px; border-top: 1px solid var(--hairline); }
    </style>

    <div class="auth-page">
        <div class="auth-box">
            <div class="auth-head">// register</div>
            <div class="auth-body">
                <div class="auth-field">
                    <label class="auth-label">name</label>
                    <input class="auth-input" type="text" wire:model="name" placeholder="your full name">
                    @error('name') <div class="auth-error">{{ $message }}</div> @enderror
                </div>
                <div class="auth-field">
                    <label class="auth-label">username</label>
                    <input class="auth-input" type="text" wire:model="username" placeholder="your_handle">
                    @error('username') <div class="auth-error">{{ $message }}</div> @enderror
                </div>
                <div class="auth-field">
                    <label class="auth-label">email</label>
                    <input class="auth-input" type="email" wire:model="email" placeholder="you@example.com">
                    @error('email') <div class="auth-error">{{ $message }}</div> @enderror
                </div>
                <div class="auth-field">
                    <label class="auth-label">password</label>
                    <input class="auth-input" type="password" wire:model="password" placeholder="min 8 chars">
                    @error('password') <div class="auth-error">{{ $message }}</div> @enderror
                </div>
                <div class="auth-field">
                    <label class="auth-label">confirm password</label>
                    <input class="auth-input" type="password" wire:model="password_confirmation">
                </div>
                <button class="btn btn-primary" wire:click="register" style="width: 100%; justify-content: center;">
                    <span wire:loading.remove wire:target="register">CREATE ACCOUNT →</span>
                    <span wire:loading wire:target="register">CREATING...</span>
                </button>
            </div>
            <div class="auth-footer">
                already have an account? <a href="/login">log in</a>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 5: Write failing login test**

```bash
php artisan make:test --pest Auth/LoginTest --no-interaction
```

Write `tests/Feature/Auth/LoginTest.php`:

```php
<?php

use App\Models\User;
use App\Livewire\Auth\Login;
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
```

- [ ] **Step 6: Run login tests to confirm they fail**

```bash
php artisan test --compact --filter=LoginTest
```

Expected: FAIL.

- [ ] **Step 7: Create Login Livewire component**

```bash
php artisan make:livewire Auth/Login --no-interaction
```

Write `app/Livewire/Auth/Login.php`:

```php
<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public function login(): void
    {
        $this->validate();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            $this->addError('email', 'These credentials do not match our records.');

            return;
        }

        session()->regenerate();

        $this->redirect(session()->pull('url.intended', '/'), navigate: true);
    }

    public function render()
    {
        if (auth()->check()) {
            return $this->redirect('/', navigate: true);
        }

        return view('livewire.auth.login')
            ->layout('layouts.app', ['title' => 'login · 518.codes']);
    }
}
```

- [ ] **Step 8: Write login view**

Create `resources/views/livewire/auth/login.blade.php`:

```blade
<div>
    <style>
        .auth-page { max-width: 480px; margin: 80px auto; padding: 0 32px; }
        .auth-box { border: 2px solid var(--fg); box-shadow: var(--shadow-2); }
        .auth-head {
            background: var(--fg); color: var(--bg); padding: 8px 14px;
            font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; font-weight: 700;
        }
        .auth-body { padding: 24px; display: flex; flex-direction: column; gap: 14px; }
        .auth-field { display: flex; flex-direction: column; gap: 4px; }
        .auth-label { font-size: 11px; letter-spacing: 0.08em; text-transform: uppercase; color: var(--fg-mute); }
        .auth-input {
            padding: 8px 12px; border: 2px solid var(--fg); background: var(--bg);
            color: var(--fg); font-family: var(--font-mono); font-size: 13px; outline: none;
        }
        .auth-input:focus { border-color: var(--accent); }
        .auth-error { font-size: 11px; color: var(--amber); margin-top: 2px; }
        .auth-footer { font-size: 13px; color: var(--fg-dim); text-align: center; padding: 14px; border-top: 1px solid var(--hairline); }
    </style>

    <div class="auth-page">
        <div class="auth-box">
            <div class="auth-head">// login</div>
            <div class="auth-body">
                <div class="auth-field">
                    <label class="auth-label">email</label>
                    <input class="auth-input" type="email" wire:model="email" placeholder="you@example.com">
                    @error('email') <div class="auth-error">{{ $message }}</div> @enderror
                </div>
                <div class="auth-field">
                    <label class="auth-label">password</label>
                    <input class="auth-input" type="password" wire:model="password">
                    @error('password') <div class="auth-error">{{ $message }}</div> @enderror
                </div>
                <button class="btn btn-primary" wire:click="login" style="width: 100%; justify-content: center;">
                    <span wire:loading.remove wire:target="login">LOGIN →</span>
                    <span wire:loading wire:target="login">CHECKING...</span>
                </button>
            </div>
            <div class="auth-footer">
                no account? <a href="/register">register</a>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 9: Add auth routes to web.php**

Add after the existing routes (before the `/` route):

```php
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use Illuminate\Support\Facades\Auth;

Route::get('/register', Register::class)->name('register');
Route::get('/login', Login::class)->name('login');
Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect('/');
})->name('logout')->middleware('auth');
```

- [ ] **Step 10: Update nav in app.blade.php**

Replace the `SUBSCRIBE →` button section in the nav with auth-aware links:

```blade
<div style="display: flex; align-items: center; gap: 12px;">
    @auth
        <a href="/members/{{ auth()->user()->username }}" class="nav-links" style="color: var(--fg-dim); text-decoration: none; font-size: 14px;">{{ auth()->user()->username }}</a>
        <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
            @csrf
            <button type="submit" class="btn btn-ghost" style="padding: 8px 16px; font-size: 12px;">LOGOUT</button>
        </form>
    @else
        <a href="{{ route('login') }}" class="btn btn-ghost" style="padding: 8px 16px; font-size: 12px; letter-spacing: 0.12em;">LOGIN</a>
        <a href="{{ route('register') }}" class="btn btn-primary" style="padding: 8px 16px; font-size: 12px; letter-spacing: 0.12em;">REGISTER →</a>
    @endauth
</div>
```

Also add `members` to the nav links list:

```blade
<li><a href="/members" @class(['active' => request()->is('members*')])>members</a></li>
```

- [ ] **Step 11: Run register and login tests**

```bash
php artisan test --compact --filter="RegisterTest|LoginTest"
```

Expected: all pass.

- [ ] **Step 12: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 13: Commit**

```bash
git add app/Livewire/Auth/ resources/views/livewire/auth/ routes/web.php resources/views/layouts/app.blade.php tests/Feature/Auth/
git commit -m "feat: add register and login Livewire components and auth routes"
```

---

## Task 6: Members index and public profile page

**Files:**
- Create: `app/Livewire/Members/MembersIndex.php`
- Create: `resources/views/livewire/members/members-index.blade.php`
- Create: `app/Livewire/Members/MemberProfile.php`
- Create: `resources/views/livewire/members/member-profile.blade.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Write failing tests for members index and profile**

```bash
php artisan make:test --pest Members/MembersIndexTest --no-interaction
php artisan make:test --pest Members/MemberProfileTest --no-interaction
```

Write `tests/Feature/Members/MembersIndexTest.php`:

```php
<?php

use App\Models\User;
use App\Livewire\Members\MembersIndex;
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
```

Write `tests/Feature/Members/MemberProfileTest.php`:

```php
<?php

use App\Models\User;
use App\Models\Skill;
use App\Models\Experience;
use App\Models\Project;
use App\Livewire\Members\MemberProfile;
use Livewire\Livewire;

test('public profile page loads', function () {
    $user = User::factory()->create(['username' => 'ada']);
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
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
php artisan test --compact --filter="MembersIndexTest|MemberProfileTest"
```

Expected: FAIL.

- [ ] **Step 3: Create Livewire components**

```bash
php artisan make:livewire Members/MembersIndex --no-interaction
php artisan make:livewire Members/MemberProfile --no-interaction
```

Write `app/Livewire/Members/MembersIndex.php`:

```php
<?php

namespace App\Livewire\Members;

use App\Models\User;
use Livewire\Attributes\Url;
use Livewire\Component;

class MembersIndex extends Component
{
    #[Url]
    public string $search = '';

    public function render()
    {
        $members = User::query()
            ->whereNotNull('username')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->get();

        return view('livewire.members.members-index', ['members' => $members])
            ->layout('layouts.app', ['title' => 'members · 518.codes']);
    }
}
```

Write `app/Livewire/Members/MemberProfile.php`:

```php
<?php

namespace App\Livewire\Members;

use App\Models\User;
use Livewire\Component;

class MemberProfile extends Component
{
    public User $member;

    public function mount(string $username): void
    {
        $this->member = User::with(['skills', 'experiences', 'projects'])
            ->where('username', $username)
            ->firstOrFail();
    }

    public function render()
    {
        $attendedMeetups = $this->member->rsvps()
            ->with('meetup')
            ->get()
            ->map(fn ($rsvp) => $rsvp->meetup)
            ->filter()
            ->filter(fn ($meetup) => $meetup->status->value === 'published');

        return view('livewire.members.member-profile', [
            'member' => $this->member,
            'attendedMeetups' => $attendedMeetups,
        ])->layout('layouts.app', ['title' => $this->member->name . ' · 518.codes']);
    }
}
```

- [ ] **Step 4: Write members index view**

Create `resources/views/livewire/members/members-index.blade.php`:

```blade
<div>
    <style>
        .members-page { max-width: 1200px; margin: 0 auto; padding: 40px 32px; }
        .members-header { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 32px; border-bottom: 2px solid var(--fg); padding-bottom: 24px; }
        .members-header h1 { font-size: 40px; font-weight: 800; letter-spacing: -0.02em; margin: 0; }
        .members-search {
            padding: 8px 12px; border: 2px solid var(--fg); background: var(--bg);
            color: var(--fg); font-family: var(--font-mono); font-size: 13px; outline: none; width: 260px;
        }
        .members-search:focus { border-color: var(--accent); }
        .members-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
        .member-card {
            border: 2px solid var(--fg); padding: 16px;
            text-decoration: none; color: inherit;
            transition: box-shadow 120ms, transform 120ms;
        }
        .member-card:hover { box-shadow: var(--shadow-2); transform: translate(-2px, -2px); background: transparent; color: inherit; }
        .member-avatar {
            width: 48px; height: 48px; border: 2px solid var(--fg);
            background: var(--surface-2); display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 18px; color: var(--accent); margin-bottom: 12px; overflow: hidden;
        }
        .member-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .member-name { font-weight: 700; font-size: 15px; margin-bottom: 2px; }
        .member-handle { font-size: 11px; color: var(--accent); letter-spacing: 0.04em; margin-bottom: 6px; }
        .member-headline { font-size: 12px; color: var(--fg-dim); }
        @media (max-width: 640px) { .members-header { flex-direction: column; gap: 16px; } .members-search { width: 100%; } }
    </style>

    <div class="members-page">
        <div class="members-header">
            <h1><span style="color: var(--accent);">›</span> members</h1>
            <input
                class="members-search"
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="search members..."
            >
        </div>

        @if ($members->isEmpty())
            <p style="color: var(--fg-mute); font-size: 14px;">No members found.</p>
        @else
            <div class="members-grid">
                @foreach ($members as $member)
                    @php $initials = strtoupper(substr($member->name, 0, 1)) . strtoupper(substr(strstr($member->name, ' '), 1, 1)); @endphp
                    <a href="/members/{{ $member->username }}" class="member-card">
                        <div class="member-avatar">
                            @if ($member->avatar_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($member->avatar_path) }}" alt="{{ $member->name }}">
                            @else
                                {{ $initials }}
                            @endif
                        </div>
                        <div class="member-name">{{ $member->name }}</div>
                        <div class="member-handle">@{{ $member->username }}</div>
                        @if ($member->headline)
                            <div class="member-headline">{{ $member->headline }}</div>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
```

- [ ] **Step 5: Write member profile view**

Create `resources/views/livewire/members/member-profile.blade.php`:

```blade
<div>
    <style>
        .profile-page { max-width: 1200px; margin: 0 auto; padding: 40px 32px 0; }
        .profile-crumbs { font-size: 13px; color: var(--fg-mute); margin-bottom: 24px; }
        .profile-crumbs a { color: var(--fg-dim); text-decoration: none; }
        .profile-crumbs a:hover { color: var(--accent); background: transparent; }
        .profile-crumbs .sep { margin: 0 8px; color: var(--accent); }
        .profile-layout { display: grid; grid-template-columns: 1fr 280px; gap: 48px; align-items: start; }
        .profile-name { font-size: 48px; font-weight: 800; letter-spacing: -0.02em; margin: 0 0 8px; }
        .profile-headline { font-size: 18px; color: var(--fg-dim); margin: 0 0 20px; }
        .profile-bio { font-size: 15px; line-height: 1.7; color: var(--fg-dim); max-width: 60ch; }
        .profile-section-title { font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--fg-mute); font-weight: 700; margin: 36px 0 12px; }
        .profile-skills { display: flex; flex-wrap: wrap; gap: 8px; }
        .profile-exp { border: 2px solid var(--fg); margin-bottom: 12px; }
        .profile-exp-head { padding: 10px 14px; border-bottom: 1px solid var(--hairline); }
        .profile-exp-title { font-weight: 700; font-size: 15px; }
        .profile-exp-company { font-size: 13px; color: var(--accent); }
        .profile-exp-years { font-size: 11px; color: var(--fg-mute); margin-top: 2px; }
        .profile-exp-desc { padding: 10px 14px; font-size: 13px; color: var(--fg-dim); }
        .profile-project { border: 2px solid var(--fg); padding: 14px; margin-bottom: 12px; }
        .profile-project-title { font-weight: 700; font-size: 15px; margin-bottom: 4px; }
        .profile-project-desc { font-size: 13px; color: var(--fg-dim); }
        .profile-sidebar-box { border: 2px solid var(--fg); margin-bottom: 16px; box-shadow: var(--shadow-2); }
        .sidebar-head { background: var(--fg); color: var(--bg); padding: 6px 12px; font-size: 10px; letter-spacing: 0.12em; text-transform: uppercase; font-weight: 700; }
        .sidebar-body { padding: 14px; }
        .profile-avatar { width: 100%; aspect-ratio: 1; object-fit: cover; border: 2px solid var(--fg); display: block; margin-bottom: 12px; }
        .profile-avatar-placeholder { width: 100%; aspect-ratio: 1; border: 2px solid var(--fg); background: var(--surface-2); display: flex; align-items: center; justify-content: center; font-size: 48px; font-weight: 800; color: var(--accent); margin-bottom: 12px; }
        .social-link { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--fg-dim); text-decoration: none; margin-bottom: 8px; }
        .social-link:hover { color: var(--accent); background: transparent; }
        .attended-item { font-size: 13px; color: var(--fg-dim); padding: 6px 0; border-bottom: 1px solid var(--hairline); }
        .attended-item:last-child { border-bottom: none; }
        .attended-item a { color: var(--accent); text-decoration: none; }
        .attended-item a:hover { background: var(--accent); color: var(--bg); }
        @media (max-width: 900px) { .profile-layout { grid-template-columns: 1fr; } }
    </style>

    <div class="profile-page">
        <div class="profile-crumbs">
            <a href="/">$ 518.codes</a>
            <span class="sep">/</span>
            <a href="/members">members</a>
            <span class="sep">/</span>
            <span>{{ $member->username }}</span>
        </div>

        <div class="profile-layout">
            <main>
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h1 class="profile-name">{{ $member->name }}</h1>
                        @if ($member->headline)
                            <p class="profile-headline">{{ $member->headline }}</p>
                        @endif
                    </div>
                    @auth
                        @if (auth()->id() === $member->id)
                            <a href="/members/{{ $member->username }}/edit" class="btn btn-ghost" style="font-size: 12px;">edit profile</a>
                        @endif
                    @endauth
                </div>

                @if ($member->bio)
                    <p class="profile-bio">{{ $member->bio }}</p>
                @endif

                @if ($member->skills->isNotEmpty())
                    <div class="profile-section-title">// skills</div>
                    <div class="profile-skills">
                        @foreach ($member->skills as $skill)
                            <span class="chip chip-accent">{{ $skill->name }}</span>
                        @endforeach
                    </div>
                @endif

                @if ($member->experiences->isNotEmpty())
                    <div class="profile-section-title">// experience</div>
                    @foreach ($member->experiences as $exp)
                        <div class="profile-exp">
                            <div class="profile-exp-head">
                                <div class="profile-exp-title">{{ $exp->title }}</div>
                                <div class="profile-exp-company">{{ $exp->company }}</div>
                                <div class="profile-exp-years">
                                    {{ $exp->start_year }} – {{ $exp->end_year ?? 'present' }}
                                </div>
                            </div>
                            @if ($exp->description)
                                <div class="profile-exp-desc">{{ $exp->description }}</div>
                            @endif
                        </div>
                    @endforeach
                @endif

                @if ($member->projects->isNotEmpty())
                    <div class="profile-section-title">// projects</div>
                    @foreach ($member->projects as $project)
                        <div class="profile-project">
                            <div class="profile-project-title">
                                @if ($project->url)
                                    <a href="{{ $project->url }}" target="_blank" rel="noopener">{{ $project->title }}</a>
                                @else
                                    {{ $project->title }}
                                @endif
                            </div>
                            @if ($project->description)
                                <div class="profile-project-desc">{{ $project->description }}</div>
                            @endif
                        </div>
                    @endforeach
                @endif
            </main>

            <aside>
                <div class="profile-sidebar-box">
                    <div class="sidebar-head">// profile</div>
                    <div class="sidebar-body">
                        @if ($member->avatar_path)
                            <img
                                class="profile-avatar"
                                src="{{ \Illuminate\Support\Facades\Storage::url($member->avatar_path) }}"
                                alt="{{ $member->name }}"
                            >
                        @else
                            @php $initials = strtoupper(substr($member->name, 0, 1)) . strtoupper(substr(strstr($member->name, ' '), 1, 1)); @endphp
                            <div class="profile-avatar-placeholder">{{ $initials }}</div>
                        @endif

                        @if ($member->company)
                            <div style="font-size: 13px; color: var(--fg-dim); margin-bottom: 12px;">{{ $member->company }}</div>
                        @endif

                        @if ($member->github_url)
                            <a href="{{ $member->github_url }}" class="social-link" target="_blank" rel="noopener">
                                <span style="color: var(--accent);">›</span> github
                            </a>
                        @endif
                        @if ($member->twitter_url)
                            <a href="{{ $member->twitter_url }}" class="social-link" target="_blank" rel="noopener">
                                <span style="color: var(--accent);">›</span> twitter
                            </a>
                        @endif
                        @if ($member->linkedin_url)
                            <a href="{{ $member->linkedin_url }}" class="social-link" target="_blank" rel="noopener">
                                <span style="color: var(--accent);">›</span> linkedin
                            </a>
                        @endif
                        @if ($member->website_url)
                            <a href="{{ $member->website_url }}" class="social-link" target="_blank" rel="noopener">
                                <span style="color: var(--accent);">›</span> website
                            </a>
                        @endif
                    </div>
                </div>

                @if ($attendedMeetups->isNotEmpty())
                    <div class="profile-sidebar-box">
                        <div class="sidebar-head">// events attended ({{ $attendedMeetups->count() }})</div>
                        <div class="sidebar-body" style="padding: 8px 14px;">
                            @foreach ($attendedMeetups as $meetup)
                                <div class="attended-item">
                                    <a href="{{ route('events.show', $meetup->slug) }}">{{ $meetup->title }}</a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </aside>
        </div>
    </div>
</div>
```

- [ ] **Step 6: Add members routes to web.php**

Add before the `/` route:

```php
use App\Livewire\Members\MembersIndex;
use App\Livewire\Members\MemberProfile;

Route::get('/members', MembersIndex::class)->name('members.index');
Route::get('/members/{username}', MemberProfile::class)->name('members.show');
```

- [ ] **Step 7: Run tests**

```bash
php artisan test --compact --filter="MembersIndexTest|MemberProfileTest"
```

Expected: all pass.

- [ ] **Step 8: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 9: Commit**

```bash
git add app/Livewire/Members/ resources/views/livewire/members/ routes/web.php tests/Feature/Members/
git commit -m "feat: add members index and public profile pages"
```

---

## Task 7: Edit profile page

**Files:**
- Create: `app/Livewire/Members/EditProfile.php`
- Create: `resources/views/livewire/members/edit-profile.blade.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Write failing edit profile tests**

```bash
php artisan make:test --pest Members/EditProfileTest --no-interaction
```

Write `tests/Feature/Members/EditProfileTest.php`:

```php
<?php

use App\Models\Skill;
use App\Models\User;
use App\Livewire\Members\EditProfile;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

test('edit profile page requires auth', function () {
    $user = User::factory()->create(['username' => 'ada']);
    $this->get('/members/ada/edit')->assertRedirect('/login');
});

test('edit profile redirects non-owner', function () {
    $owner = User::factory()->create(['username' => 'ada']);
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
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
php artisan test --compact --filter=EditProfileTest
```

Expected: FAIL.

- [ ] **Step 3: Create EditProfile Livewire component**

```bash
php artisan make:livewire Members/EditProfile --no-interaction
```

Write `app/Livewire/Members/EditProfile.php`:

```php
<?php

namespace App\Livewire\Members;

use App\Models\Skill;
use App\Models\User;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditProfile extends Component
{
    use WithFileUploads;

    public User $member;

    #[Validate('nullable|string|max:255')]
    public string $headline = '';

    #[Validate('nullable|string|max:2000')]
    public string $bio = '';

    #[Validate('nullable|string|max:255')]
    public string $company = '';

    #[Validate('nullable|url|max:255')]
    public string $websiteUrl = '';

    #[Validate('nullable|url|max:255')]
    public string $githubUrl = '';

    #[Validate('nullable|url|max:255')]
    public string $twitterUrl = '';

    #[Validate('nullable|url|max:255')]
    public string $linkedinUrl = '';

    /** @var array<int> */
    public array $selectedSkillIds = [];

    #[Validate('nullable|image|max:2048')]
    public $avatar;

    public function mount(string $username): void
    {
        $this->member = User::where('username', $username)->firstOrFail();

        abort_if(auth()->id() !== $this->member->id, 403);

        $this->headline = $this->member->headline ?? '';
        $this->bio = $this->member->bio ?? '';
        $this->company = $this->member->company ?? '';
        $this->websiteUrl = $this->member->website_url ?? '';
        $this->githubUrl = $this->member->github_url ?? '';
        $this->twitterUrl = $this->member->twitter_url ?? '';
        $this->linkedinUrl = $this->member->linkedin_url ?? '';
        $this->selectedSkillIds = $this->member->skills->pluck('id')->toArray();
    }

    public function save(): void
    {
        $this->validate();

        $avatarPath = $this->member->avatar_path;
        if ($this->avatar) {
            $avatarPath = $this->avatar->store('avatars', 'public');
        }

        $this->member->update([
            'headline' => $this->headline ?: null,
            'bio' => $this->bio ?: null,
            'company' => $this->company ?: null,
            'avatar_path' => $avatarPath,
            'website_url' => $this->websiteUrl ?: null,
            'github_url' => $this->githubUrl ?: null,
            'twitter_url' => $this->twitterUrl ?: null,
            'linkedin_url' => $this->linkedinUrl ?: null,
        ]);

        $this->member->skills()->sync($this->selectedSkillIds);

        $this->redirect("/members/{$this->member->username}", navigate: true);
    }

    public function render()
    {
        return view('livewire.members.edit-profile', [
            'allSkills' => Skill::orderBy('name')->get(),
        ])->layout('layouts.app', ['title' => 'edit profile · 518.codes']);
    }
}
```

- [ ] **Step 4: Write edit profile view**

Create `resources/views/livewire/members/edit-profile.blade.php`:

```blade
<div>
    <style>
        .edit-page { max-width: 760px; margin: 60px auto; padding: 0 32px 80px; }
        .edit-box { border: 2px solid var(--fg); box-shadow: var(--shadow-2); }
        .edit-head { background: var(--fg); color: var(--bg); padding: 8px 14px; font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; font-weight: 700; }
        .edit-body { padding: 24px; display: flex; flex-direction: column; gap: 18px; }
        .edit-section-title { font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--accent); font-weight: 700; padding-top: 8px; border-top: 1px solid var(--hairline); }
        .edit-field { display: flex; flex-direction: column; gap: 4px; }
        .edit-label { font-size: 11px; letter-spacing: 0.08em; text-transform: uppercase; color: var(--fg-mute); }
        .edit-input {
            padding: 8px 12px; border: 2px solid var(--fg); background: var(--bg);
            color: var(--fg); font-family: var(--font-mono); font-size: 13px; outline: none;
        }
        .edit-input:focus { border-color: var(--accent); }
        .edit-textarea { resize: vertical; min-height: 80px; }
        .edit-error { font-size: 11px; color: var(--amber); margin-top: 2px; }
        .skills-grid { display: flex; flex-wrap: wrap; gap: 8px; }
        .skill-toggle { cursor: pointer; }
        .skill-toggle input { display: none; }
        .skill-toggle .chip { cursor: pointer; }
        .skill-toggle input:checked + .chip { background: var(--accent); color: var(--bg); border-color: var(--accent); }
    </style>

    <div class="edit-page">
        <div class="edit-box">
            <div class="edit-head">// edit profile · {{ $member->username }}</div>
            <div class="edit-body">

                <div class="edit-section-title">basics</div>

                <div class="edit-field">
                    <label class="edit-label">headline</label>
                    <input class="edit-input" type="text" wire:model="headline" placeholder="e.g. Full-stack dev at Acme">
                    @error('headline') <div class="edit-error">{{ $message }}</div> @enderror
                </div>

                <div class="edit-field">
                    <label class="edit-label">bio</label>
                    <textarea class="edit-input edit-textarea" wire:model="bio" placeholder="A bit about yourself..."></textarea>
                    @error('bio') <div class="edit-error">{{ $message }}</div> @enderror
                </div>

                <div class="edit-field">
                    <label class="edit-label">company</label>
                    <input class="edit-input" type="text" wire:model="company" placeholder="Where do you work?">
                    @error('company') <div class="edit-error">{{ $message }}</div> @enderror
                </div>

                <div class="edit-field">
                    <label class="edit-label">avatar</label>
                    <input class="edit-input" type="file" wire:model="avatar" accept="image/*">
                    @error('avatar') <div class="edit-error">{{ $message }}</div> @enderror
                </div>

                <div class="edit-section-title">skills</div>

                <div class="skills-grid">
                    @foreach ($allSkills as $skill)
                        <label class="skill-toggle">
                            <input
                                type="checkbox"
                                value="{{ $skill->id }}"
                                wire:model="selectedSkillIds"
                            >
                            <span class="chip chip-accent">{{ $skill->name }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="edit-section-title">socials</div>

                <div class="edit-field">
                    <label class="edit-label">github url</label>
                    <input class="edit-input" type="url" wire:model="githubUrl" placeholder="https://github.com/you">
                    @error('githubUrl') <div class="edit-error">{{ $message }}</div> @enderror
                </div>

                <div class="edit-field">
                    <label class="edit-label">twitter url</label>
                    <input class="edit-input" type="url" wire:model="twitterUrl" placeholder="https://twitter.com/you">
                    @error('twitterUrl') <div class="edit-error">{{ $message }}</div> @enderror
                </div>

                <div class="edit-field">
                    <label class="edit-label">linkedin url</label>
                    <input class="edit-input" type="url" wire:model="linkedinUrl" placeholder="https://linkedin.com/in/you">
                    @error('linkedinUrl') <div class="edit-error">{{ $message }}</div> @enderror
                </div>

                <div class="edit-field">
                    <label class="edit-label">website url</label>
                    <input class="edit-input" type="url" wire:model="websiteUrl" placeholder="https://yoursite.com">
                    @error('websiteUrl') <div class="edit-error">{{ $message }}</div> @enderror
                </div>

                <button class="btn btn-primary" wire:click="save" style="width: 100%; justify-content: center;">
                    <span wire:loading.remove wire:target="save">SAVE PROFILE →</span>
                    <span wire:loading wire:target="save">SAVING...</span>
                </button>

            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 5: Add edit profile route to web.php**

Add after the members.show route:

```php
Route::get('/members/{username}/edit', \App\Livewire\Members\EditProfile::class)
    ->name('members.edit')
    ->middleware('auth');
```

- [ ] **Step 6: Run tests**

```bash
php artisan test --compact --filter=EditProfileTest
```

Expected: all pass.

- [ ] **Step 7: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 8: Commit**

```bash
git add app/Livewire/Members/EditProfile.php resources/views/livewire/members/edit-profile.blade.php routes/web.php tests/Feature/Members/EditProfileTest.php
git commit -m "feat: add edit profile page with skills, socials, and avatar upload"
```

---

## Task 8: RSVP → account creation flow

**Files:**
- Modify: `app/Livewire/EventDetail.php`
- Modify: `resources/views/livewire/event-detail.blade.php`

- [ ] **Step 1: Write failing tests**

```bash
php artisan make:test --pest EventDetailRsvpAccountTest --no-interaction
```

Write `tests/Feature/EventDetailRsvpAccountTest.php`:

```php
<?php

use App\Enums\MeetupStatus;
use App\Livewire\EventDetail;
use App\Models\Meetup;
use App\Models\Rsvp;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

test('rsvp links to existing user account by email', function () {
    $user = User::factory()->create(['email' => 'ada@example.com', 'username' => 'ada']);
    $meetup = Meetup::factory()->create(['status' => MeetupStatus::Published, 'starts_at' => now()->addDays(7)]);

    Livewire::test(EventDetail::class, ['slug' => $meetup->slug])
        ->set('name', 'Ada Tang')
        ->set('email', 'ada@example.com')
        ->call('rsvp')
        ->assertSet('rsvpd', true)
        ->assertSet('showPasswordPrompt', false);

    $rsvp = Rsvp::where('meetup_id', $meetup->id)->where('email', 'ada@example.com')->first();
    expect($rsvp->user_id)->toBe($user->id);
});

test('rsvp shows password prompt for unknown email', function () {
    $meetup = Meetup::factory()->create(['status' => MeetupStatus::Published, 'starts_at' => now()->addDays(7)]);

    Livewire::test(EventDetail::class, ['slug' => $meetup->slug])
        ->set('name', 'New Person')
        ->set('email', 'new@example.com')
        ->call('rsvp')
        ->assertSet('rsvpd', true)
        ->assertSet('showPasswordPrompt', true);
});

test('creating account from rsvp logs user in and links rsvp', function () {
    $meetup = Meetup::factory()->create(['status' => MeetupStatus::Published, 'starts_at' => now()->addDays(7)]);

    $component = Livewire::test(EventDetail::class, ['slug' => $meetup->slug])
        ->set('name', 'New Person')
        ->set('email', 'new@example.com')
        ->call('rsvp');

    $component
        ->set('newPassword', 'password123')
        ->call('createAccount');

    expect(auth()->check())->toBeTrue();
    expect(auth()->user()->email)->toBe('new@example.com');

    $rsvp = Rsvp::where('meetup_id', $meetup->id)->where('email', 'new@example.com')->first();
    expect($rsvp->user_id)->toBe(auth()->user()->id);
});

test('skipping password prompt leaves rsvp anonymous', function () {
    $meetup = Meetup::factory()->create(['status' => MeetupStatus::Published, 'starts_at' => now()->addDays(7)]);

    Livewire::test(EventDetail::class, ['slug' => $meetup->slug])
        ->set('name', 'Anonymous Person')
        ->set('email', 'anon@example.com')
        ->call('rsvp')
        ->call('skipAccountCreation')
        ->assertSet('showPasswordPrompt', false);

    $rsvp = Rsvp::where('meetup_id', $meetup->id)->where('email', 'anon@example.com')->first();
    expect($rsvp->user_id)->toBeNull();
});
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
php artisan test --compact --filter=EventDetailRsvpAccountTest
```

Expected: FAIL.

- [ ] **Step 3: Update EventDetail component**

Replace `app/Livewire/EventDetail.php`:

```php
<?php

namespace App\Livewire;

use App\Enums\MeetupStatus;
use App\Models\Meetup;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class EventDetail extends Component
{
    public Meetup $meetup;

    public bool $rsvpd = false;

    public bool $showPasswordPrompt = false;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|min:8')]
    public string $newPassword = '';

    private ?Rsvp $currentRsvp = null;

    public function mount(string $slug): void
    {
        $this->meetup = Meetup::with(['tags', 'rsvps', 'scheduleItems', 'images'])
            ->where('slug', $slug)
            ->where('status', MeetupStatus::Published)
            ->firstOrFail();
    }

    public function rsvp(): void
    {
        $this->validateOnly('name');
        $this->validateOnly('email');

        $existingUser = User::where('email', $this->email)->first();

        try {
            $this->currentRsvp = $this->meetup->rsvps()->create([
                'name' => $this->name,
                'email' => $this->email,
                'user_id' => $existingUser?->id,
            ]);
        } catch (UniqueConstraintViolationException) {
            $this->addError('email', 'This email is already registered for this event.');

            return;
        }

        $this->rsvpd = true;
        $this->meetup->load('rsvps');

        if ($existingUser === null) {
            $this->showPasswordPrompt = true;
        }
    }

    public function createAccount(): void
    {
        $this->validateOnly('newPassword');

        $username = $this->generateUsername($this->name);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'username' => $username,
            'password' => Hash::make($this->newPassword),
        ]);

        Auth::login($user);

        Rsvp::where('meetup_id', $this->meetup->id)
            ->where('email', $this->email)
            ->update(['user_id' => $user->id]);

        $this->showPasswordPrompt = false;
    }

    public function skipAccountCreation(): void
    {
        $this->showPasswordPrompt = false;
    }

    private function generateUsername(string $name): string
    {
        $base = Str::slug($name);
        $username = $base;
        $i = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base . $i;
            $i++;
        }

        return $username;
    }

    public function render()
    {
        return view('livewire.event-detail')
            ->layout('layouts.app', [
                'title' => $this->meetup->title . ' · 518.codes',
            ]);
    }
}
```

- [ ] **Step 4: Add password prompt and linked roster to event-detail.blade.php**

After the RSVP confirmation button block (the `@if ($rsvpd)` block), add the password prompt inside the existing `@if ($rsvpd)` block:

```blade
@if ($rsvpd)
    <button class="btn btn-primary" disabled style="width: 100%; justify-content: center;">
        ✓ YOU'RE GOING
    </button>
    @if ($showPasswordPrompt)
        <div style="border: 1px solid var(--hairline); padding: 12px; margin-top: 8px;">
            <div style="font-size: 11px; letter-spacing: 0.08em; text-transform: uppercase; color: var(--fg-mute); margin-bottom: 8px;">
                save your profile?
            </div>
            <input
                class="manifest-input"
                type="password"
                placeholder="choose a password"
                wire:model="newPassword"
            >
            @error('newPassword') <div class="manifest-error">{{ $message }}</div> @enderror
            <div style="display: flex; gap: 8px; margin-top: 8px;">
                <button class="btn btn-primary" wire:click="createAccount" style="flex: 1; justify-content: center; font-size: 11px;">
                    CREATE ACCOUNT
                </button>
                <button class="btn btn-ghost" wire:click="skipAccountCreation" style="font-size: 11px;">
                    skip
                </button>
            </div>
        </div>
    @endif
@else
```

For the roster, update the `roster-row` name cell. Replace:

```blade
<span class="roster-name">{{ $rsvp->name }}</span>
```

With:

```blade
<span class="roster-name">
    @if ($rsvp->user?->username)
        <a href="/members/{{ $rsvp->user->username }}" style="color: var(--accent); text-decoration: none;">
            {{ $rsvp->name }}
        </a>
    @else
        {{ $rsvp->name }}
    @endif
</span>
```

Also update the meetup load in `mount` to eager load the user:

In `EventDetail.php` `mount()`, change:
```php
$this->meetup = Meetup::with(['tags', 'rsvps', 'scheduleItems', 'images'])
```
to:
```php
$this->meetup = Meetup::with(['tags', 'rsvps.user', 'scheduleItems', 'images'])
```

- [ ] **Step 5: Run tests**

```bash
php artisan test --compact --filter=EventDetailRsvpAccountTest
```

Expected: all pass.

- [ ] **Step 6: Run existing RSVP tests to ensure no regressions**

```bash
php artisan test --compact --filter=EventDetailRsvpTest
```

Expected: all pass.

- [ ] **Step 7: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 8: Commit**

```bash
git add app/Livewire/EventDetail.php resources/views/livewire/event-detail.blade.php tests/Feature/EventDetailRsvpAccountTest.php
git commit -m "feat: RSVP flow optionally creates account and links roster names to profiles"
```

---

## Task 9: Filament — is_admin gate + Users resource + Skills resource

**Files:**
- Modify: `app/Providers/Filament/AdminPanelProvider.php` (register new resources)
- Create: `app/Filament/Resources/Users/UserResource.php`
- Create: `app/Filament/Resources/Users/Pages/ListUsers.php`
- Create: `app/Filament/Resources/Users/Pages/EditUser.php`
- Create: `app/Filament/Resources/Users/Schemas/UserForm.php`
- Create: `app/Filament/Resources/Users/Tables/UsersTable.php`
- Create: `app/Filament/Resources/Skills/SkillResource.php`
- Create: `app/Filament/Resources/Skills/Pages/ListSkills.php`
- Create: `app/Filament/Resources/Skills/Pages/CreateSkill.php`
- Create: `app/Filament/Resources/Skills/Pages/EditSkill.php`
- Create: `app/Filament/Resources/Skills/Schemas/SkillForm.php`
- Create: `app/Filament/Resources/Skills/Tables/SkillsTable.php`

- [ ] **Step 1: Write failing Filament tests**

```bash
php artisan make:test --pest Filament/UserResourceTest --no-interaction
php artisan make:test --pest Filament/SkillResourceTest --no-interaction
```

Write `tests/Feature/Filament/UserResourceTest.php`:

```php
<?php

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

it('can list users', function () {
    $users = User::factory(3)->create();

    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users);
});

it('can edit a user', function () {
    $user = User::factory()->create(['username' => 'bob']);

    livewire(EditUser::class, ['record' => $user->id])
        ->fillForm(['username' => 'bobby'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh()->username)->toBe('bobby');
});

it('can toggle is_admin', function () {
    $user = User::factory()->create(['is_admin' => false]);

    livewire(EditUser::class, ['record' => $user->id])
        ->fillForm(['is_admin' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh()->is_admin)->toBeTrue();
});
```

Write `tests/Feature/Filament/SkillResourceTest.php`:

```php
<?php

use App\Filament\Resources\Skills\Pages\CreateSkill;
use App\Filament\Resources\Skills\Pages\EditSkill;
use App\Filament\Resources\Skills\Pages\ListSkills;
use App\Models\Skill;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

it('can list skills', function () {
    $skills = Skill::factory(3)->create();

    livewire(ListSkills::class)
        ->assertCanSeeTableRecords($skills);
});

it('can create a skill', function () {
    livewire(CreateSkill::class)
        ->fillForm(['name' => 'Laravel', 'slug' => 'laravel'])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    assertDatabaseHas(Skill::class, ['name' => 'Laravel', 'slug' => 'laravel']);
});

it('can edit a skill', function () {
    $skill = Skill::factory()->create();

    livewire(EditSkill::class, ['record' => $skill->id])
        ->fillForm(['name' => 'Updated'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($skill->fresh()->name)->toBe('Updated');
});
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
php artisan test --compact --filter="UserResourceTest|SkillResourceTest"
```

Expected: FAIL.

- [ ] **Step 3: Generate Filament resources**

```bash
php artisan make:filament-resource User --generate --no-interaction
php artisan make:filament-resource Skill --generate --no-interaction
```

- [ ] **Step 4: Move generated files to match project structure**

The generator creates `app/Filament/Resources/UserResource.php` etc. Move to subdirectory structure:

```bash
mkdir -p app/Filament/Resources/Users/Pages app/Filament/Resources/Users/Schemas app/Filament/Resources/Users/Tables
mkdir -p app/Filament/Resources/Skills/Pages app/Filament/Resources/Skills/Schemas app/Filament/Resources/Skills/Tables

mv app/Filament/Resources/UserResource.php app/Filament/Resources/Users/UserResource.php
mv app/Filament/Resources/UserResource/Pages/ListUsers.php app/Filament/Resources/Users/Pages/ListUsers.php
mv app/Filament/Resources/UserResource/Pages/CreateUser.php app/Filament/Resources/Users/Pages/CreateUser.php
mv app/Filament/Resources/UserResource/Pages/EditUser.php app/Filament/Resources/Users/Pages/EditUser.php

mv app/Filament/Resources/SkillResource.php app/Filament/Resources/Skills/SkillResource.php
mv app/Filament/Resources/SkillResource/Pages/ListSkills.php app/Filament/Resources/Skills/Pages/ListSkills.php
mv app/Filament/Resources/SkillResource/Pages/CreateSkill.php app/Filament/Resources/Skills/Pages/CreateSkill.php
mv app/Filament/Resources/SkillResource/Pages/EditSkill.php app/Filament/Resources/Skills/Pages/EditSkill.php

rm -rf app/Filament/Resources/UserResource app/Filament/Resources/SkillResource
```

- [ ] **Step 5: Write UserResource**

Write `app/Filament/Resources/Users/UserResource.php`:

```php
<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('username')->unique(ignoreRecord: true)->alpha_dash(),
            Toggle::make('is_admin')->label('Admin access'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('username')->searchable(),
                IconColumn::make('is_admin')->boolean()->label('Admin'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 6: Write User Pages**

Write `app/Filament/Resources/Users/Pages/ListUsers.php`:

```php
<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
}
```

Write `app/Filament/Resources/Users/Pages/EditUser.php`:

```php
<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
}
```

- [ ] **Step 7: Write SkillResource**

Write `app/Filament/Resources/Skills/SkillResource.php`:

```php
<?php

namespace App\Filament\Resources\Skills;

use App\Filament\Resources\Skills\Pages\CreateSkill;
use App\Filament\Resources\Skills\Pages\EditSkill;
use App\Filament\Resources\Skills\Pages\ListSkills;
use App\Models\Skill;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Illuminate\Support\Str;

class SkillResource extends Resource
{
    protected static ?string $model = Skill::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
            TextInput::make('slug')
                ->required()
                ->unique(ignoreRecord: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug'),
                TextColumn::make('users_count')->counts('users')->label('Users'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSkills::route('/'),
            'create' => CreateSkill::route('/create'),
            'edit' => EditSkill::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 8: Write Skill Pages**

Write `app/Filament/Resources/Skills/Pages/ListSkills.php`:

```php
<?php

namespace App\Filament\Resources\Skills\Pages;

use App\Filament\Resources\Skills\SkillResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ListSkills extends ListRecords
{
    protected static string $resource = SkillResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
```

Write `app/Filament/Resources/Skills/Pages/CreateSkill.php`:

```php
<?php

namespace App\Filament\Resources\Skills\Pages;

use App\Filament\Resources\Skills\SkillResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSkill extends CreateRecord
{
    protected static string $resource = SkillResource::class;
}
```

Write `app/Filament/Resources/Skills/Pages/EditSkill.php`:

```php
<?php

namespace App\Filament\Resources\Skills\Pages;

use App\Filament\Resources\Skills\SkillResource;
use Filament\Resources\Pages\EditRecord;

class EditSkill extends EditRecord
{
    protected static string $resource = SkillResource::class;
}
```

- [ ] **Step 9: Register resources in AdminPanelProvider**

In `app/Providers/Filament/AdminPanelProvider.php`, add to the `->resources([...])` call:

```php
\App\Filament\Resources\Users\UserResource::class,
\App\Filament\Resources\Skills\SkillResource::class,
```

- [ ] **Step 10: Run tests**

```bash
php artisan test --compact --filter="UserResourceTest|SkillResourceTest"
```

Expected: all pass.

- [ ] **Step 11: Run the full test suite to check for regressions**

```bash
php artisan test --compact
```

Expected: all pass.

- [ ] **Step 12: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 13: Commit**

```bash
git add app/Filament/Resources/Users/ app/Filament/Resources/Skills/ app/Providers/Filament/ tests/Feature/Filament/
git commit -m "feat: add Users and Skills Filament resources, gate admin panel with is_admin"
```

---

## Task 10: Final — run full suite and seed admin users

- [ ] **Step 1: Run the complete test suite**

```bash
php artisan test --compact
```

Expected: all tests pass with no failures.

- [ ] **Step 2: Run TeamSeeder to set is_admin on existing team members**

```bash
php artisan db:seed --class=TeamSeeder --no-interaction
```

- [ ] **Step 3: Verify admin access**

Log in to `/admin` with one of the team member emails to confirm `is_admin` gates access correctly.

- [ ] **Step 4: Final commit**

```bash
git add -p
git commit -m "feat: auth + member profiles complete"
```
