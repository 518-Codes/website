<?php

namespace App\Casts;

use App\DTOs\NotificationPreferences;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<NotificationPreferences, NotificationPreferences>
 */
class NotificationPreferencesCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): NotificationPreferences
    {
        if ($value === null) {
            return new NotificationPreferences;
        }

        if (! json_validate($value)) {
            return new NotificationPreferences;
        }

        $data = json_decode($value, true);

        return new NotificationPreferences(
            rsvpConfirmation: $data['rsvp_confirmation'] ?? true,
            announcements: $data['announcements'] ?? true,
            remindersEnabled: $data['reminders_enabled'] ?? true,
            reminderTiming: $data['reminder_timing'] ?? ['24h', '1h'],
        );
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if ($value instanceof NotificationPreferences) {
            return json_encode([
                'rsvp_confirmation' => $value->rsvpConfirmation,
                'announcements' => $value->announcements,
                'reminders_enabled' => $value->remindersEnabled,
                'reminder_timing' => $value->reminderTiming,
            ]);
        }

        throw new \InvalidArgumentException(
            'Value must be an instance of '.NotificationPreferences::class
        );
    }
}
