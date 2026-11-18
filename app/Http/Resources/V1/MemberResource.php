<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * A member's public profile. Never includes email or account settings.
 *
 * @mixin User
 */
class MemberResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap = 'data';

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'username' => $this->username,
            'name' => $this->name,
            'headline' => $this->headline,
            'bio' => $this->bio,
            'company' => $this->company,
            'avatar_url' => $this->avatar_path ? Storage::url($this->avatar_path) : null,
            'links' => [
                'website' => $this->website_url,
                'github' => $this->github_url,
                'twitter' => $this->twitter_url,
                'linkedin' => $this->linkedin_url,
            ],
            'skills' => $this->whenLoaded('skills', fn () => $this->skills->pluck('name')),
            'experiences' => $this->whenLoaded('experiences', fn () => $this->experiences->map(fn ($experience) => [
                'title' => $experience->title,
                'company' => $experience->company,
                'start_year' => $experience->start_year,
                'end_year' => $experience->end_year,
                'description' => $experience->description,
            ])),
            'projects' => $this->whenLoaded('projects', fn () => $this->projects->map(fn ($project) => [
                'title' => $project->title,
                'url' => $project->url,
                'description' => $project->description,
            ])),
            'member_since' => $this->created_at?->toIso8601String(),
            'url' => route('members.show', $this->username),
        ];
    }
}
