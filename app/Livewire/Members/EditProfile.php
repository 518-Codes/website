<?php

namespace App\Livewire\Members;

use App\Models\Experience;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Str;
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

    public string $newSkillName = '';

    /** @var array<int, array{id: int, name: string}> */
    public array $skillOptions = [];

    #[Validate('nullable|image|max:2048')]
    public $avatar;

    /**
     * @var array<int, array{id: int|null, title: string, company: string, start_year: string, end_year: string, description: string}>
     */
    public array $experiences = [];

    /**
     * @var array<int, array{id: int|null, title: string, url: string, description: string}>
     */
    public array $projects = [];

    public function mount(string $username): void
    {
        $this->member = User::where('username', $username)
            ->with(['skills', 'experiences', 'projects'])
            ->firstOrFail();

        abort_if(auth()->id() !== $this->member->id, 403);

        $this->headline = $this->member->headline ?? '';
        $this->bio = $this->member->bio ?? '';
        $this->company = $this->member->company ?? '';
        $this->websiteUrl = $this->member->website_url ?? '';
        $this->githubUrl = $this->member->github_url ?? '';
        $this->twitterUrl = $this->member->twitter_url ?? '';
        $this->linkedinUrl = $this->member->linkedin_url ?? '';
        $this->selectedSkillIds = $this->member->skills->pluck('id')->toArray();
        $this->skillOptions = Skill::orderBy('name')->get()->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()->toArray();

        $this->experiences = $this->member->experiences->map(fn (Experience $e) => [
            'id' => $e->id,
            'title' => $e->title,
            'company' => $e->company ?? '',
            'start_year' => (string) $e->start_year,
            'end_year' => (string) ($e->end_year ?? ''),
            'description' => $e->description ?? '',
        ])->toArray();

        $this->projects = $this->member->projects->map(fn (Project $p) => [
            'id' => $p->id,
            'title' => $p->title,
            'url' => $p->url ?? '',
            'description' => $p->description ?? '',
        ])->toArray();
    }

    public function addSkill(): void
    {
        $name = trim($this->newSkillName);
        if ($name === '') {
            return;
        }

        $skill = Skill::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name],
        );

        if (! in_array($skill->id, $this->selectedSkillIds)) {
            $this->selectedSkillIds[] = $skill->id;
        }

        $this->newSkillName = '';
        $this->skillOptions = Skill::orderBy('name')->get()->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()->toArray();
    }

    public function addExperience(): void
    {
        $this->experiences[] = [
            'id' => null,
            'title' => '',
            'company' => '',
            'start_year' => (string) now()->year,
            'end_year' => '',
            'description' => '',
        ];
    }

    public function removeExperience(int $index): void
    {
        $exp = $this->experiences[$index] ?? null;
        if ($exp && $exp['id']) {
            Experience::destroy($exp['id']);
        }
        array_splice($this->experiences, $index, 1);
    }

    public function addProject(): void
    {
        $this->projects[] = [
            'id' => null,
            'title' => '',
            'url' => '',
            'description' => '',
        ];
    }

    public function removeProject(int $index): void
    {
        $proj = $this->projects[$index] ?? null;
        if ($proj && $proj['id']) {
            Project::destroy($proj['id']);
        }
        array_splice($this->projects, $index, 1);
    }

    public function save(): void
    {
        $this->validate();

        $this->validate([
            'experiences.*.title' => 'required|string|max:255',
            'experiences.*.company' => 'nullable|string|max:255',
            'experiences.*.start_year' => 'required|digits:4|integer|min:1970|max:2099',
            'experiences.*.end_year' => 'nullable|digits:4|integer|min:1970|max:2099',
            'experiences.*.description' => 'nullable|string|max:2000',
            'projects.*.title' => 'required|string|max:255',
            'projects.*.url' => 'nullable|url|max:255',
            'projects.*.description' => 'nullable|string|max:2000',
        ]);

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

        $existingExpIds = collect($this->experiences)->pluck('id')->filter()->all();
        $this->member->experiences()->whereNotIn('id', $existingExpIds)->delete();
        foreach ($this->experiences as $exp) {
            $this->member->experiences()->updateOrCreate(
                ['id' => $exp['id'] ?: 0],
                [
                    'title' => $exp['title'],
                    'company' => $exp['company'] ?: null,
                    'start_year' => (int) $exp['start_year'],
                    'end_year' => $exp['end_year'] ? (int) $exp['end_year'] : null,
                    'description' => $exp['description'] ?: null,
                ],
            );
        }

        $existingProjIds = collect($this->projects)->pluck('id')->filter()->all();
        $this->member->projects()->whereNotIn('id', $existingProjIds)->delete();
        foreach ($this->projects as $proj) {
            $this->member->projects()->updateOrCreate(
                ['id' => $proj['id'] ?: 0],
                [
                    'title' => $proj['title'],
                    'url' => $proj['url'] ?: null,
                    'description' => $proj['description'] ?: null,
                ],
            );
        }

        $this->redirect("/members/{$this->member->username}", navigate: true);
    }

    public function render()
    {
        return view('livewire.members.edit-profile')
            ->layout('layouts.app', ['title' => 'edit profile · 518.codes']);
    }
}
