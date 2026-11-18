<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MeetupStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\EventResource;
use App\Models\Meetup;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Events', weight: 1)]
class EventController extends Controller
{
    /**
     * List events
     *
     * Upcoming events, soonest first. Pass `filter=past` for past events, most recent first.
     *
     * @unauthenticated
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'filter' => ['sometimes', 'in:upcoming,past'],
            /** Results per page, up to 100. */
            'per_page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $query = Meetup::published()->with('tags')->withCount('rsvps');

        $query = $request->input('filter') === 'past'
            ? $query->past()->orderByDesc('starts_at')
            : $query->upcoming()->orderBy('starts_at');

        return EventResource::collection($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    /**
     * Show an event
     *
     * One published event with its venue and RSVP count.
     *
     * @unauthenticated
     */
    public function show(Meetup $event): EventResource
    {
        abort_unless($event->status === MeetupStatus::Published, 404);

        return new EventResource($event->load('tags')->loadCount('rsvps'));
    }
}
