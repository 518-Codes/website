<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MeetupStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\EventResource;
use App\Models\Meetup;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

#[Group('Events', weight: 1)]
class EventRsvpController extends Controller
{
    /**
     * RSVP to an event
     *
     * RSVP the current member. Returns 201 for a new RSVP and 200 if they had already RSVP'd.
     */
    public function store(Request $request, Meetup $event): JsonResponse
    {
        abort_unless($event->status === MeetupStatus::Published, 404);

        if ($event->hasEnded()) {
            throw ValidationException::withMessages(['event' => 'This event has already ended.']);
        }

        $user = $request->user();

        $rsvp = $event->rsvps()->firstOrCreate(
            ['email' => $user->email],
            ['name' => $user->name, 'user_id' => $user->id],
        );

        $resource = new EventResource($event->load('tags')->loadCount('rsvps'));

        if ($rsvp->wasRecentlyCreated) {
            return $resource->response()->setStatusCode(201);
        }

        return $resource->response();
    }

    /**
     * Cancel an RSVP
     *
     * Cancel the current member's RSVP. Returns 404 if they had not RSVP'd.
     */
    public function destroy(Request $request, Meetup $event): Response
    {
        abort_unless($event->status === MeetupStatus::Published, 404);

        $user = $request->user();

        $deleted = $event->rsvps()
            ->where(fn (Builder $query) => $query->where('user_id', $user->id)->orWhere('email', $user->email))
            ->delete();

        abort_if($deleted === 0, 404);

        return response()->noContent();
    }
}
