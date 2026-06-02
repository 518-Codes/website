<?php

namespace App\Models;

use Database\Factories\RsvpFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['meetup_id', 'user_id', 'name', 'email'])]
class Rsvp extends Model
{
    /** @use HasFactory<RsvpFactory> */
    use HasFactory;

    public function meetup(): BelongsTo
    {
        return $this->belongsTo(Meetup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
