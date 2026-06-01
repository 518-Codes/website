<?php

namespace App\Http\Resources;

use App\Models\Meetup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Meetup
 */
class MeetupMapResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'location' => $this->location,
            'lat' => $this->latitude,
            'lng' => $this->longitude,
            'starts_at' => $this->starts_at->toIso8601String(),
            'url' => url('/events/'.$this->slug),
        ];
    }
}
