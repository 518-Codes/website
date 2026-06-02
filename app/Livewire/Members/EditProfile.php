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
