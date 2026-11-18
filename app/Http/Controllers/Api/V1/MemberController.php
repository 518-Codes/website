<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\MemberResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Members', weight: 2)]
class MemberController extends Controller
{
    /**
     * List members
     *
     * The public member directory, sorted by name.
     *
     * @unauthenticated
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            /** Filter members whose name contains this text. */
            'search' => ['sometimes', 'string', 'max:255'],
            /** Results per page, up to 100. */
            'per_page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $members = User::query()
            ->whereNotNull('username')
            ->when($request->input('search'), fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(min($request->integer('per_page', 20), 100));

        return MemberResource::collection($members);
    }

    /**
     * Show a member
     *
     * One member's public profile, with skills, experience and projects.
     *
     * @unauthenticated
     */
    public function show(User $member): MemberResource
    {
        return new MemberResource($member->load(['skills', 'experiences', 'projects']));
    }
}
