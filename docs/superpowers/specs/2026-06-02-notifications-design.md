# Notifications Design

**Date:** 2026-06-02  
**Status:** Approved

## Overview

Email notifications for 518.codes members and guests, plus Discord alerts for admins. Covers RSVP confirmations, meetup announcements, meetup reminders, and admin alerts for new RSVPs, registrations, and host submissions.

---

## 1. Data Model

A `notification_preferences` JSON column is added to the `users` table. It is cast to a typed readonly DTO.

### DTO

```php
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
```

A custom `CastsAttributes` cast class serializes/deserializes this DTO to/from JSON on the `User` model. `$user->notification_preferences` always returns a `NotificationPreferences` instance.

### Migration

Add `notification_preferences` JSON column to `users` table with a default covering all preferences enabled.

### Guest handling

Guests who RSVP without an account have no preference record. They always receive the RSVP confirmation email sent to their plain email address via `Notification::route('mail', $email)`.

---

## 2. Notification Classes

All classes use the `mail` channel. Located in `app/Notifications/`.

| Class | Trigger | Preference checked |
|---|---|---|
| `RsvpConfirmation` | RSVP created | `rsvp_confirmation` (guests always receive) |
| `MeetupAnnouncement` | Meetup status → Published | `announcements` |
| `MeetupReminder` | Scheduled command | `reminders_enabled` + timing in `reminder_timing` |

`MeetupReminder` accepts a `string $timing` constructor parameter (`'24h'` or `'1h'`) to vary the subject line and body copy without duplicating the class.

---

## 3. Observers

Model observers handle notification dispatch and Discord alerts. Located in `app/Observers/`.

| Observer | Model event | Action |
|---|---|---|
| `RsvpObserver::created` | Rsvp created | Dispatch `SendRsvpConfirmation` queued job; send Discord alert |
| `MeetupObserver::updated` | Meetup status transitions to Published | Dispatch `SendMeetupAnnouncement` queued job (fans out per-user, respects `announcements` preference) |
| `UserObserver::created` | User created | Send Discord alert |

Observers are registered in `AppServiceProvider` via `Model::observe()`.

`MeetupObserver` checks `$meetup->wasChanged('status')` and that the new status is `MeetupStatus::Published` and the old value was not `Published` — ensuring the announcement only fires on the transition, not on subsequent saves.

### Host event submissions

`HostEvent` is a Livewire form, not a persisted model, so it cannot use an observer. A `HostEventSubmitted` event is fired from the `HostEvent` component on successful submission. A `SendHostEventDiscordAlert` listener handles the Discord call.

---

## 4. Scheduled Reminders

Two artisan commands: `notifications:send-reminders` accepts a `--timing` option (`24h` or `1h`).

**Schedule** (in `routes/console.php`):
- `--timing=24h` runs every 15 minutes, queries meetups starting between 23h45m and 24h15m from now
- `--timing=1h` runs every 15 minutes, queries meetups starting between 45m and 1h15m from now

The time window approach (±15 minutes around the target) ensures the command catches the right meetups without double-sending when run on a 15-minute cron.

For each matching meetup, the command loads all RSVPed users (with accounts) who have `reminders_enabled = true` and the relevant timing value in `reminder_timing`, then dispatches `MeetupReminder` notifications.

---

## 5. Discord Alerts

A `DiscordWebhook` service class in `app/Services/` wraps `Http::post()` to the configured webhook URL.

**Config:** `config/services.php` key `discord.webhook_url`, populated from `DISCORD_WEBHOOK_URL` in `.env`.

The service is bound in the container so it can be swapped with a fake in tests.

| Trigger | Message format |
|---|---|
| New RSVP | `🎟️ New RSVP: {name} ({email}) for {meetup title}` |
| New user registered | `👤 New member: {name} ({email})` |
| Host event submitted | `📋 New host submission from {name} ({email})` |

---

## 6. Notification Preferences UI

A new "Notification Preferences" section is added to the existing `EditProfile` Livewire component and its view (`members/{username}/edit`). No new page or route needed.

**Controls:**

- **RSVP Confirmations** — on/off toggle. Always-on by default. Copy: "Receive a confirmation email when you RSVP for an event."
- **Event Announcements** — on/off toggle. Copy: "Get notified when a new meetup is published."
- **Reminders** — on/off toggle. When enabled, shows a checkbox group: "24 hours before" and "1 hour before". Checkboxes are hidden when reminders are toggled off. At least one timing must be selected when reminders are enabled (validated server-side).

The `EditProfile` component adds properties bound to the DTO fields and saves them alongside the rest of the profile on form submit.

---

## 7. Testing

| Test | Type |
|---|---|
| `RsvpObserver` sends `RsvpConfirmation` to RSVPed user | Feature |
| Guest RSVP sends `RsvpConfirmation` to plain email address | Feature |
| `RsvpObserver` sends Discord alert on RSVP created | Feature |
| `MeetupObserver` sends `MeetupAnnouncement` on status transition to Published | Feature |
| `MeetupObserver` does not send announcement on non-status save | Feature |
| `UserObserver` sends Discord alert on user created | Feature |
| `HostEventSubmitted` listener sends Discord alert | Feature |
| `SendMeetupReminders --timing=24h` notifies opted-in users only | Feature |
| `SendMeetupReminders --timing=1h` notifies opted-in users only | Feature |
| `SendMeetupReminders` does not double-send if run twice in same window | Feature |
| User with `announcements: false` excluded from announcement send | Feature |
| `NotificationPreferences` DTO serializes/deserializes correctly | Unit |
| Preference toggles save correctly from `EditProfile` | Feature |

All notification tests use `Notification::fake()`. Discord tests use `Http::fake()`.

---

## 8. File Inventory

```
app/
  DTOs/
    NotificationPreferences.php
  Casts/
    NotificationPreferencesCast.php
  Notifications/
    RsvpConfirmation.php
    MeetupAnnouncement.php
    MeetupReminder.php
  Observers/
    RsvpObserver.php
    MeetupObserver.php
    UserObserver.php
  Events/
    HostEventSubmitted.php
  Listeners/
    SendHostEventDiscordAlert.php
  Services/
    DiscordWebhook.php
  Console/
    Commands/
      SendMeetupReminders.php
database/
  migrations/
    xxxx_add_notification_preferences_to_users_table.php
```
