# Meetups Feature — Design Spec

**Date:** 2026-05-26
**Status:** Approved

## Overview

Add a Meetups feature to the 518.codes admin panel. This covers the Eloquent models, migrations, and Filament resources for managing local developer meetups, their tags, images, and guest RSVPs.

---

## Data Models

### `meetups`

| Column | Type | Notes |
|---|---|---|
| `id` | bigIncrements | |
| `title` | string | |
| `slug` | string | unique, auto-generated from title |
| `description` | longText | |
| `location` | string | physical venue name/address |
| `starts_at` | datetime | |
| `ends_at` | datetime | nullable |
| `status` | string | default `draft`; not a DB enum |
| `timestamps` | | |

### `tags`

| Column | Type | Notes |
|---|---|---|
| `id` | bigIncrements | |
| `name` | string | |
| `slug` | string | unique, auto-generated from name |
| `timestamps` | | |

### `meetup_tag` (pivot)

| Column | Type |
|---|---|
| `meetup_id` | foreignId |
| `tag_id` | foreignId |

### `images`

| Column | Type | Notes |
|---|---|---|
| `id` | bigIncrements | |
| `imageable_id` | unsignedBigInteger | polymorphic |
| `imageable_type` | string | polymorphic |
| `path` | string | stored file path |
| `alt` | string | nullable |
| `order` | unsignedInteger | default `0` |
| `timestamps` | | |

### `rsvps`

| Column | Type | Notes |
|---|---|---|
| `id` | bigIncrements | |
| `meetup_id` | foreignId | cascades on delete |
| `name` | string | |
| `email` | string | |
| `timestamps` | | |

Unique constraint on `(meetup_id, email)` to prevent duplicate RSVPs.

---

## Eloquent Models

### `Meetup`
- `belongsToMany(Tag::class)` via `meetup_tag`
- `hasMany(Rsvp::class)`
- `morphMany(Image::class, 'imageable')`
- Casts `status` to `MeetupStatus` (PHP string-backed enum)
- `MeetupStatus` cases: `Draft = 'draft'`, `Published = 'published'`, `Cancelled = 'cancelled'`

### `Tag`
- `belongsToMany(Meetup::class)` via `meetup_tag`

### `Image`
- `morphTo('imageable')`

### `Rsvp`
- `belongsTo(Meetup::class)`

---

## PHP Enum

```php
enum MeetupStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Cancelled = 'cancelled';
}
```

Stored as a string in the DB. Never use a MySQL ENUM column — string columns are easier to alter later.

---

## Filament Resources

### `MeetupResource`

**List table columns:**
- `title`
- `location`
- `starts_at` (formatted date/time)
- `status` (badge with color: Draft=gray, Published=green, Cancelled=red)
- RSVP count (computed from relationship)

**Form fields:**
- `title` (TextInput, live on blur → auto-sets slug)
- `slug` (TextInput, editable)
- `description` (RichEditor)
- `location` (TextInput)
- `starts_at` / `ends_at` (DateTimePicker, side by side)
- `status` (Select from `MeetupStatus` enum)
- `tags` (Select with `->multiple()->relationship('tags', 'name')`)
- Images (Repeater → `->relationship('images')` with file upload (`->visibility('public')`) + alt TextInput + hidden order)

**Pages:** List, Create, Edit (no standalone View page — Edit page shows all detail)

**Relation Manager:** `RsvpsRelationManager` on the Edit page
- Table columns: name, email, created_at
- Read-only table (no create action — RSVPs come from the frontend)
- Delete action allowed

---

### `TagResource`

**List table columns:**
- `name`
- `slug`
- Meetup count (computed from relationship)

**Form fields:**
- `name` (TextInput, live on blur → auto-sets slug)
- `slug` (TextInput, editable)

**Pages:** List, Create, Edit

---

### `RsvpResource` (global view)

**List table columns:**
- Meetup title (via relationship)
- `name`
- `email`
- `created_at`

**Pages:** List only — no create or edit. Delete action on table rows.

---

## Factories & Seeders

- `MeetupFactory` with realistic fake data, random status
- `TagFactory`
- `RsvpFactory`
- `DatabaseSeeder` wires them together: seed N tags, seed N meetups each with random tags and RSVPs

---

## Out of Scope

- Speaker management
- Virtual/hybrid meetup URLs
- Authenticated user RSVPs (guest name/email only for now)
- Frontend display pages (admin panel only)
- Email confirmation on RSVP
