<?php

namespace App\Http\Controllers;

use App\Http\Resources\MeetupMapResource;
use App\Models\Meetup;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MapEventsController extends Controller
{
    /**
     * Published, upcoming meetups that have coordinates, soonest first.
     * The browser computes recency/size against the real clock, so this list
     * is unfiltered by horizon and stays cacheable.
     */
    public function __invoke(): AnonymousResourceCollection
    {
        $events = Meetup::query()
            ->published()
            ->upcoming()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('starts_at')
            ->get();

        return MeetupMapResource::collection($events);
    }
}
