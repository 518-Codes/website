<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * The authenticated member's own profile, including private account fields.
 *
 * @mixin User
 */
class MeResource extends MemberResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $preferences = $this->notification_preferences;

        return [
            ...parent::toArray($request),
            'email' => $this->email,
            'notification_preferences' => [
                'rsvp_confirmation' => $preferences->rsvpConfirmation,
                'announcements' => $preferences->announcements,
                'reminders_enabled' => $preferences->remindersEnabled,
                'reminder_timing' => $preferences->reminderTiming,
            ],
        ];
    }
}
