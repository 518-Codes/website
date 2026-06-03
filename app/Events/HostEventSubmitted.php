<?php

namespace App\Events;

use App\Models\Meetup;
use Illuminate\Foundation\Events\Dispatchable;

class HostEventSubmitted
{
    use Dispatchable;

    public function __construct(
        public readonly Meetup $meetup,
    ) {}
}
