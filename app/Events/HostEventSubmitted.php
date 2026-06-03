<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class HostEventSubmitted
{
    use Dispatchable;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}
