<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['meetup_id', 'time', 'title', 'note', 'order'])]
class MeetupScheduleItem extends Model
{
    public function meetup(): BelongsTo
    {
        return $this->belongsTo(Meetup::class);
    }
}
