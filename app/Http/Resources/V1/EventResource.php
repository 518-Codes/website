<?php

namespace App\Http\Resources\V1;

use App\Models\Meetup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Meetup
 */
class EventResource extends JsonResource
{
    /**
     * The v1 API wraps every resource in a "data" key, unlike the site's flat internal endpoints.
     *
     * @var string|null
     */
    public static $wrap = 'data';

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->plainDescription(),
            'what_to_expect' => $this->plainWhatToExpect(),
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'venue' => [
                'location' => $this->location,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')),
            'rsvp_count' => $this->whenCounted('rsvps'),
            'url' => route('events.show', $this->slug),
        ];
    }
}
