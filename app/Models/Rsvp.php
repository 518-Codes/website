<?php

namespace App\Models;

use App\Observers\RsvpObserver;
use Database\Factories\RsvpFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['meetup_id', 'user_id', 'name', 'email', 'reminder_24h_sent_at', 'reminder_1h_sent_at'])]
#[ObservedBy(RsvpObserver::class)]
class Rsvp extends Model
{
    /** @use HasFactory<RsvpFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'reminder_24h_sent_at' => 'datetime',
            'reminder_1h_sent_at' => 'datetime',
        ];
    }

    public function meetup(): BelongsTo
    {
        return $this->belongsTo(Meetup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
