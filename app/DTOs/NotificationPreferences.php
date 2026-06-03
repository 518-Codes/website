<?php

namespace App\DTOs;

readonly class NotificationPreferences
{
    public function __construct(
        public bool $rsvpConfirmation = true,
        public bool $announcements = true,
        public bool $remindersEnabled = true,
        /** @var array<string> */
        public array $reminderTiming = ['24h', '1h'],
    ) {}
}
