<?php

namespace App\Models;

use App\Enums\MeetupStatus;
use App\Observers\MeetupObserver;
use Database\Factories\MeetupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['title', 'slug', 'description', 'what_to_expect', 'location', 'latitude', 'longitude', 'starts_at', 'ends_at', 'status', 'contact_email'])]
#[ObservedBy(MeetupObserver::class)]
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

    /**
     * The description as plain text: HTML tags stripped and entities decoded,
     * suitable for meta tags, calendar files, and truncated previews.
     */
    public function plainDescription(): string
    {
        return $this->toPlainText($this->description);
    }

    /**
     * The "what to expect" copy as plain text, for notification emails.
     */
    public function plainWhatToExpect(): string
    {
        return $this->toPlainText($this->what_to_expect);
    }

    private function toPlainText(?string $html): string
    {
        return trim(html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
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

    public function isInProgress(): bool
    {
        return $this->starts_at->isPast() && $this->ends_at?->isFuture() === true;
    }

    /**
     * Meetups that have not started yet or are currently in progress.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('starts_at', '>=', now())
                ->orWhere('ends_at', '>=', now());
        });
    }
}
