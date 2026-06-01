<?php

namespace App\Models;

use App\Enums\MeetupStatus;
use Database\Factories\MeetupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['title', 'slug', 'description', 'what_to_expect', 'location', 'latitude', 'longitude', 'starts_at', 'ends_at', 'status', 'contact_email'])]
class Meetup extends Model
{
    /** @use HasFactory<MeetupFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => MeetupStatus::class,
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(Rsvp::class);
    }

    public function scheduleItems(): HasMany
    {
        return $this->hasMany(MeetupScheduleItem::class)->orderBy('order');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('order');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', MeetupStatus::Published);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now());
    }
}
