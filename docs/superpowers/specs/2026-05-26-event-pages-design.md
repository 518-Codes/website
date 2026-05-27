# Event Pages Design Spec
_2026-05-26_

## Scope

Two new public pages plus a shared layout extraction:

1. `/events` — events listing page
2. `/events/{slug}` — single event detail page
3. `layouts/app.blade.php` — shared layout (refactor home page to use it)

## Architecture

### Routes

```
GET  /events              → Livewire\EventsIndex (full-page component)
GET  /events/{slug}       → Livewire\EventDetail (full-page component)
```

No API route. RSVP is handled via a Livewire action on `EventDetail`.

### Shared Layout — `resources/views/layouts/app.blade.php`

Contains:
- `<html>`, `<head>` with charset, viewport, Vite (`app.css`, `app.js`), Google Fonts (JetBrains Mono + VT323)
- Design token CSS block (currently inlined in `welcome.blade.php`) — moved here, defined once
- Sticky nav: `$518.codes` wordmark left, links (`events` / `about` / `host`) middle, `SUBSCRIBE →` primary button right. Active link passed via `@stack` or a `$activeNav` variable
- Footer: four-column grid (brand + desc / community links / subscribe links / region list), phosphor `─` rule separator, `© 518.codes · est. 2024` bottom bar
- `@yield('content')` slot for page body

`welcome.blade.php` is refactored to `@extends('layouts.app')` with no visual changes.

### Livewire Components

**`App\Livewire\EventsIndex`**
- Queries `Meetup::published()->upcoming()->with(['tags','rsvps'])->orderBy('starts_at')`
- Groups results by month in the component (`groupBy`)
- No filtering logic yet — full list rendered server-side
- View: `resources/views/livewire/events-index.blade.php`

**`App\Livewire\EventDetail`**
- Resolves meetup by `slug`, 404 if not found or not `Published`
- Public property: `bool $rsvpd = false`
- Livewire action: `rsvp()` — creates an `Rsvp` record for the meetup, sets `$rsvpd = true`. No auth, no duplicate guard for now.
- View: `resources/views/livewire/event-detail.blade.php`

### Scopes on Meetup (add if not present)

```php
scopePublished(Builder $q): Builder  // where status = Published
scopeUpcoming(Builder $q): Builder   // where starts_at >= now()
```

## Visual Design

### Events Listing (`/events`)

**Page heading** (full width, bordered bottom):
- `$ events --upcoming` — 56px bold, phosphor `$` prefix
- Subhead in `fg-dim`: "Everything on the calendar. RSVPs are free and non-binding."
- Right side: event count + city count meta label, `+ ical` and `rss` ghost buttons

**Two-column layout**: main (1fr) + sidebar (320px), 56px gap

**Main — month-grouped list:**
- Each month group: `MAY 2026` h2 + hairline rule + `N events` count label
- Event rows identical to home page calendar list (`EventRow` pattern: VT323 date left, title + chips + venue/time middle, going count right)
- Empty state: `// no upcoming events` with link to host

**Sidebar — three stacked bordered cards (2px border, 4px hard shadow):**
1. `// quick stats` — key/value rows: events shown, total going, cities, next event countdown
2. `// subscribe` — label, one-line email input + `→` submit button inline
3. `// host` — short copy + `SUBMIT EVENT →` primary button full-width

### Event Detail (`/events/{slug}`)

**Breadcrumb:** `$ 518.codes / events / {slug}` in `fg-mute`, links on first two segments

**Header (two-column, bordered bottom, 40px padding-bottom):**
- Left: tag chips row, `h1` at 56px, lede paragraph (`fg-dim`, 18px, max 56ch)
- Right (340px): **manifest panel** — bordered surface card, dark header bar `// event.manifest`, `dl` key/value grid:
  - `when` — day + date + year, time range (accent on day)
  - `where` — venue name + city
  - `cost` — `$0` in accent
  - `going` — live count
  - Actions below a hairline: full-width RSVP button + full-width ghost "add to calendar" button

**RSVP button states:**
- Default: `RSVP · {N} GOING` (primary style)
- After `rsvp()` fires: `✓ YOU'RE GOING` (primary style, disabled, no further clicks)

**Body (two-column, same widths):**

Main:
- `› what to expect` — meetup description rendered as paragraphs
- `› schedule` — bordered table: VT323 time column (accent) + title/note column, hairline-separated rows
- `› who's going` — 8-column monogram grid (2-letter initials from RSVP records, last cell shows `+N` overflow in accent if > 16)

Sidebar:
- `// where` card — venue name + full location address
- ASCII map schematic block — phosphor text on surface bg, fixed monospace art representing the venue area (static placeholder)

## Out of Scope

- Search / tag filtering on the listing (deferred)
- Un-RSVP / waitlist
- Duplicate RSVP guard
- Auth
- Real map embed
- Calendar invite generation
- Past events archive
