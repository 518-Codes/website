<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\EventResource;
use App\Http\Resources\V1\MeResource;
use App\Models\Meetup;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Me', weight: 3)]
class MeController extends Controller
{
    /**
     * Show my profile
     *
     * The current member's profile, email and notification settings.
     */
    public function show(Request $request): MeResource
    {
        return new MeResource($request->user()->load(['skills', 'experiences', 'projects']));
    }

    /**
     * Update my profile
     *
     * Update any subset of the current member's profile fields.
     */
    public function update(Request $request): MeResource
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'headline' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'website_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'github_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'twitter_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'linkedin_url' => ['sometimes', 'nullable', 'url', 'max:255'],
        ]);

        $user = $request->user();
        $user->update($validated);

        return new MeResource($user->load(['skills', 'experiences', 'projects']));
    }

    /**
     * List my RSVPs
     *
     * Events the current member has RSVP'd to, most recent first.
     */
    public function rsvps(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            /** Results per page, up to 100. */
            'per_page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $user = $request->user();

        $events = Meetup::published()
            ->whereHas('rsvps', fn (Builder $query) => $query
                ->where('user_id', $user->id)
                ->orWhere('email', $user->email))
            ->with('tags')
            ->withCount('rsvps')
            ->orderByDesc('starts_at')
            ->paginate(min($request->integer('per_page', 20), 100));

        return EventResource::collection($events);
    }
}
