# Event Pages Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement `/events` listing and `/events/{slug}` detail pages with a shared Blade layout, Livewire components, and a functional RSVP action.

**Architecture:** Extract a shared `layouts/app.blade.php` containing nav, footer, and design tokens. Two Livewire full-page components (`EventsIndex`, `EventDetail`) handle data fetching and the RSVP action. The home page is refactored to extend the shared layout with no visual change.

**Tech Stack:** Laravel 13, Livewire 4, Blade, JetBrains Mono + VT323 (Google Fonts), Pest 4, inline CSS design tokens (no Tailwind classes on these pages).

---

## File Map

| Action | Path | Responsibility |
|--------|------|----------------|
| Create | `resources/views/layouts/app.blade.php` | Shared HTML shell, nav, footer, design token CSS |
| Modify | `resources/views/welcome.blade.php` | Extend shared layout, remove duplicated shell |
| Modify | `app/Models/Meetup.php` | Add `scopePublished` and `scopeUpcoming` |
| Create | `app/Livewire/EventsIndex.php` | Query + group meetups by month |
| Create | `resources/views/livewire/events-index.blade.php` | Events listing UI |
| Create | `app/Livewire/EventDetail.php` | Resolve by slug, `rsvp()` action |
| Create | `resources/views/livewire/event-detail.blade.php` | Event detail UI with manifest panel |
| Modify | `routes/web.php` | Add `/events` and `/events/{slug}` routes |
| Modify | `tests/Feature/ExampleTest.php` | Add smoke tests for new routes |
| Create | `tests/Feature/EventDetailRsvpTest.php` | RSVP action tests |

---

## Task 1: Shared layout — `layouts/app.blade.php`

**Files:**
- Create: `resources/views/layouts/app.blade.php`
- Modify: `resources/views/welcome.blade.php`

- [ ] **Step 1: Create the shared layout**

Create `resources/views/layouts/app.blade.php` with this exact content:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '518.codes — meetups for people who write code in the 518')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;700;800&family=VT323&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --term-bg: #0B0F0B;
            --term-bg-2: #121812;
            --term-bg-3: #1A211A;
            --term-fg: #E6FFE6;
            --term-fg-dim: #9AB89C;
            --term-fg-mute: #5A6E5C;
            --phosphor: #5EFC8D;
            --phosphor-dim: #2DC964;
            --amber: #FFB627;
            --magenta: #FF3DAA;
            --cyan: #5EE2FC;
            --rule: #1F2A1F;
            --rule-2: #2A3A2A;
            --bg: var(--term-bg);
            --surface: var(--term-bg-2);
            --surface-2: var(--term-bg-3);
            --fg: var(--term-fg);
            --fg-dim: var(--term-fg-dim);
            --fg-mute: var(--term-fg-mute);
            --accent: var(--phosphor);
            --border: var(--term-fg);
            --hairline: var(--rule);
            --shadow-1: 2px 2px 0 0 var(--fg);
            --shadow-2: 4px 4px 0 0 var(--fg);
            --shadow-3: 6px 6px 0 0 var(--fg);
            --shadow-phosphor: 4px 4px 0 0 var(--phosphor);
            --font-mono: 'JetBrains Mono', ui-monospace, monospace;
            --font-display: 'VT323', 'JetBrains Mono', monospace;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0;
            background: var(--bg);
            color: var(--fg);
            font-family: var(--font-mono);
            font-size: 15px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        a { color: var(--accent); text-decoration: underline; text-decoration-thickness: 1px; text-underline-offset: 3px; }
        a:hover { background: var(--accent); color: var(--bg); text-decoration: none; }
        ::selection { background: var(--accent); color: var(--bg); }

        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: var(--surface); }
        ::-webkit-scrollbar-thumb { background: var(--rule-2); border: 2px solid var(--surface); }
        ::-webkit-scrollbar-thumb:hover { background: var(--accent); }

        /* Nav */
        .nav {
            display: flex; align-items: center; justify-content: space-between;
            padding: 20px 32px;
            border-bottom: 2px solid var(--fg);
            position: sticky; top: 0; background: var(--bg); z-index: 100;
        }
        .nav-brand { font-weight: 800; font-size: 18px; letter-spacing: -0.02em; text-decoration: none; color: inherit; }
        .nav-brand .dollar { color: var(--accent); margin-right: 4px; }
        .nav-brand .dot { color: var(--accent); }
        .nav-links { display: flex; gap: 28px; list-style: none; padding: 0; margin: 0; font-size: 14px; }
        .nav-links a { color: var(--fg-dim); text-decoration: none; border-bottom: 2px solid transparent; padding-bottom: 2px; }
        .nav-links a:hover { color: var(--accent); background: transparent; border-bottom-color: var(--accent); }
        .nav-links a.active { color: var(--accent); border-bottom-color: var(--accent); }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; gap: 10px;
            font-family: var(--font-mono); font-weight: 700; font-size: 14px;
            padding: 12px 20px; border: 3px solid var(--fg);
            cursor: pointer; text-decoration: none;
            transition: transform 150ms cubic-bezier(.2,.9,.25,1), box-shadow 150ms cubic-bezier(.2,.9,.25,1), background 150ms cubic-bezier(.2,.9,.25,1), color 150ms cubic-bezier(.2,.9,.25,1);
            letter-spacing: 0.02em;
        }
        .btn-primary { background: var(--accent); color: var(--bg); border-color: var(--bg); box-shadow: 6px 6px 0 0 var(--fg); }
        .btn-primary:hover { transform: translate(6px,6px); box-shadow: none; background: var(--accent); color: var(--bg); }
        .btn-secondary { background: transparent; color: var(--fg); box-shadow: 6px 6px 0 0 var(--fg); }
        .btn-secondary:hover { transform: translate(6px,6px); box-shadow: none; background: var(--accent); color: var(--bg); border-color: var(--bg); }
        .btn-ghost { background: transparent; color: var(--fg); border-color: transparent; box-shadow: none; padding: 12px 6px; }
        .btn-ghost:hover { background: var(--surface-2); color: var(--fg); }
        .btn:disabled, .btn[disabled] { opacity: 0.5; cursor: not-allowed; transform: none !important; }

        /* Kicker */
        .kicker { font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; color: var(--accent); font-weight: 600; }
        .kicker-mute { color: var(--fg-mute); }

        /* Chip */
        .chip { display: inline-flex; align-items: center; padding: 2px 8px; border: 1.5px solid currentColor; font-size: 11px; letter-spacing: 0.04em; line-height: 1.4; color: var(--fg-dim); }
        .chip-accent { color: var(--accent); }
        .chip-amber { color: var(--amber); }
        .chip-cyan { color: var(--cyan); }
        .chip-magenta { color: var(--magenta); }

        /* Footer */
        .footer { border-top: 2px solid var(--fg); padding: 40px 32px 32px; margin-top: 96px; }
        .footer-rule { color: var(--accent); overflow: hidden; white-space: nowrap; font-family: var(--font-mono); font-size: 13px; line-height: 1; user-select: none; margin-bottom: 32px; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 32px; }
        .footer-desc { color: var(--fg-dim); font-size: 13px; margin-top: 12px; max-width: 32ch; line-height: 1.5; }
        .footer-col-title { font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; color: var(--fg-mute); font-weight: 600; margin-bottom: 10px; }
        .footer-link { display: block; margin-bottom: 6px; color: var(--fg-dim); text-decoration: none; font-size: 14px; }
        .footer-link:hover { color: var(--accent); background: transparent; }
        .footer-bottom { display: flex; justify-content: space-between; align-items: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--hairline); color: var(--fg-mute); font-size: 12px; }
        .footer-status { color: var(--accent); }

        @media (max-width: 640px) {
            .nav { padding: 16px 20px; }
            .nav-links { display: none; }
            .footer-grid { grid-template-columns: 1fr; }
        }

        @yield('styles')
    </style>
    @livewireStyles
</head>
<body>

<nav class="nav">
    <a href="/" class="nav-brand">
        <span class="dollar">$</span>518<span class="dot">.</span>codes
    </a>
    <ul class="nav-links">
        <li><a href="/events" @class(['active' => request()->is('events*')])>events</a></li>
        <li><a href="#about">about</a></li>
        <li><a href="#host">host</a></li>
    </ul>
    <a href="#subscribe" class="btn btn-primary" style="padding: 8px 16px; font-size: 12px; letter-spacing: 0.12em;">
        SUBSCRIBE →
    </a>
</nav>

@yield('content')

<footer class="footer">
    <div class="footer-rule" aria-hidden="true">{{ str_repeat('─', 400) }}</div>
    <div class="footer-grid">
        <div>
            <span style="font-weight: 800; font-size: 20px; letter-spacing: -0.02em;">
                <span style="color: var(--accent); margin-right: 4px;">$</span>518<span style="color: var(--accent);">.</span>codes
            </span>
            <p class="footer-desc">A community of software developers in New York's Capital Region. Free, volunteer-run, and always open to first-timers.</p>
        </div>
        <div>
            <div class="footer-col-title">community</div>
            @foreach(['events' => '/events', 'host an event' => '#host', 'code of conduct' => '#', 'about' => '#about'] as $label => $href)
                <a href="{{ $href }}" class="footer-link">› {{ $label }}</a>
            @endforeach
        </div>
        <div>
            <div class="footer-col-title">subscribe</div>
            @foreach(['weekly digest', 'rss feed', 'ical link', 'discord'] as $link)
                <a href="#" class="footer-link">› {{ $link }}</a>
            @endforeach
        </div>
        <div>
            <div class="footer-col-title">region</div>
            <div style="color: var(--fg-dim); font-size: 13px; line-height: 1.8;">
                Albany<br>Troy<br>Schenectady<br>Saratoga Springs<br>Hudson Valley
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div>© 518.codes · est. 2024 · made in upstate ny</div>
        <div><span class="footer-status">● online</span></div>
    </div>
</footer>

@livewireScripts
</body>
</html>
```

- [ ] **Step 2: Refactor `welcome.blade.php` to extend the layout**

Replace the entire `welcome.blade.php` file. Remove the `<html>`, `<head>`, `<body>` shell, the nav, the footer, and the `<style>` block (they now live in the layout). Keep all content sections. The new file should look like:

```blade
@extends('layouts.app')

@section('title', '518.codes — meetups for people who write code in the 518')

@section('styles')
{{-- home-page-specific styles only (scanlines, hero, ticker, sections, etc.) --}}
{{-- Copy everything from the existing <style> block EXCEPT the :root tokens,
     html/body rules, a/::selection rules, scrollbar rules, .nav-*, .btn-*, .kicker,
     .chip, .footer-* rules — those now live in the layout. --}}
@endsection

@section('content')
{{-- Everything between <body> and </body> in the current welcome.blade.php
     EXCEPT the <nav>...</nav> and <footer>...</footer> blocks. --}}
@endsection
```

> **Tip:** Open `welcome.blade.php`, identify the nav block (starts `<nav class="nav">`, ends `</nav>`), the footer block (starts `<footer class="footer">`, ends `</footer>`), the `<style>` block, and the outer `<html>`/`<head>`/`<body>` tags. Remove all of these — keep only the sections in between.

- [ ] **Step 3: Verify home page still works**

```bash
curl -s http://518codes.test/ | grep -c "SHOW UP"
# Expected output: 1
curl -s http://518codes.test/ | grep -c "nav class"
# Expected output: 1
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/layouts/app.blade.php resources/views/welcome.blade.php
git commit -m "refactor: extract shared Blade layout with nav, footer, and design tokens"
```

---

## Task 2: Meetup model scopes

**Files:**
- Modify: `app/Models/Meetup.php`

- [ ] **Step 1: Write failing tests for the scopes**

Create `tests/Feature/MeetupScopeTest.php`:

```bash
php artisan make:test --pest MeetupScopeTest
```

Replace its contents with:

```php
<?php

use App\Enums\MeetupStatus;
use App\Models\Meetup;

test('scopePublished returns only published meetups', function () {
    Meetup::factory()->create(['status' => MeetupStatus::Published]);
    Meetup::factory()->create(['status' => MeetupStatus::Draft]);
    Meetup::factory()->create(['status' => MeetupStatus::Cancelled]);

    expect(Meetup::published()->count())->toBe(1);
});

test('scopeUpcoming returns meetups starting from now onwards', function () {
    Meetup::factory()->create(['starts_at' => now()->addDay()]);
    Meetup::factory()->create(['starts_at' => now()->subDay()]);

    expect(Meetup::upcoming()->count())->toBe(1);
});

test('scopes can be chained', function () {
    Meetup::factory()->create(['status' => MeetupStatus::Published, 'starts_at' => now()->addDay()]);
    Meetup::factory()->create(['status' => MeetupStatus::Published, 'starts_at' => now()->subDay()]);
    Meetup::factory()->create(['status' => MeetupStatus::Draft, 'starts_at' => now()->addDay()]);

    expect(Meetup::published()->upcoming()->count())->toBe(1);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact --filter=MeetupScopeTest
# Expected: 3 failed — "Call to undefined method scopePublished / scopeUpcoming"
```

- [ ] **Step 3: Add scopes to `Meetup` model**

In `app/Models/Meetup.php`, add the import and two scope methods:

```php
use Illuminate\Database\Eloquent\Builder;
```

Add these methods inside the class body (after the `casts()` method):

```php
public function scopePublished(Builder $query): Builder
{
    return $query->where('status', MeetupStatus::Published);
}

public function scopeUpcoming(Builder $query): Builder
{
    return $query->where('starts_at', '>=', now());
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
php artisan test --compact --filter=MeetupScopeTest
# Expected: 3 passed
```

- [ ] **Step 5: Commit**

```bash
git add app/Models/Meetup.php tests/Feature/MeetupScopeTest.php
git commit -m "feat: add scopePublished and scopeUpcoming to Meetup model"
```

---

## Task 3: `EventsIndex` Livewire component

**Files:**
- Create: `app/Livewire/EventsIndex.php`
- Create: `resources/views/livewire/events-index.blade.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Scaffold the component**

```bash
php artisan make:livewire EventsIndex --no-interaction
```

- [ ] **Step 2: Write `app/Livewire/EventsIndex.php`**

Replace the generated file with:

```php
<?php

namespace App\Livewire;

use App\Models\Meetup;
use Illuminate\Support\Collection;
use Livewire\Component;

class EventsIndex extends Component
{
    public function meetupsByMonth(): Collection
    {
        return Meetup::published()
            ->upcoming()
            ->with(['tags', 'rsvps'])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Meetup $meetup) => $meetup->starts_at->format('F Y'));
    }

    public function render()
    {
        return view('livewire.events-index', [
            'meetupsByMonth' => $this->meetupsByMonth(),
        ])->layout('layouts.app', ['title' => 'events · 518.codes']);
    }
}
```

- [ ] **Step 3: Write the events listing view**

Replace `resources/views/livewire/events-index.blade.php` with:

```blade
<div>
    <style>
        .events-page { max-width: 1200px; margin: 0 auto; padding: 56px 32px 0; }
        .events-page-head {
            display: grid; grid-template-columns: 1fr auto; gap: 24px; align-items: end;
            margin-bottom: 40px; border-bottom: 2px solid var(--fg); padding-bottom: 24px;
        }
        .events-page-head h1 { font-size: 56px; letter-spacing: -0.02em; margin: 0; line-height: 1; font-weight: 800; }
        .events-page-head h1 .pr { color: var(--accent); }
        .events-page-head .sub { color: var(--fg-dim); margin-top: 12px; max-width: 56ch; font-size: 15px; }

        .events-layout { display: grid; grid-template-columns: 1fr 320px; gap: 56px; }

        .month-head {
            display: flex; align-items: baseline; gap: 18px;
            margin: 56px 0 8px;
        }
        .month-head:first-child { margin-top: 0; }
        .month-head h2 { font-size: 24px; margin: 0; font-weight: 700; letter-spacing: -0.01em; white-space: nowrap; }
        .month-head .month-rule { flex: 1; border-top: 1px solid var(--hairline); }
        .month-head .month-count { color: var(--fg-mute); font-size: 12px; letter-spacing: 0.12em; text-transform: uppercase; white-space: nowrap; }

        .event-row {
            display: grid; grid-template-columns: 96px 1fr auto;
            gap: 20px; align-items: center;
            padding: 22px 0; border-bottom: 1px solid var(--hairline);
            color: inherit; text-decoration: none;
            transition: background 120ms;
        }
        .event-row:hover { background: var(--surface); padding-left: 12px; padding-right: 12px; margin: 0 -12px; }
        .event-date { font-family: var(--font-display); color: var(--accent); line-height: 1; }
        .event-date .day { font-size: 40px; line-height: 1; }
        .event-date .month-dow { font-size: 14px; letter-spacing: 0.1em; }
        .event-title-text { font-weight: 700; font-size: 17px; }
        .event-chips { display: flex; gap: 8px; align-items: center; margin-top: 4px; flex-wrap: wrap; }
        .event-sub { font-size: 12px; color: var(--fg-mute); letter-spacing: 0.04em; margin-top: 4px; }
        .event-going { color: var(--accent); font-size: 13px; white-space: nowrap; }

        .side-card {
            border: 2px solid var(--fg); padding: 18px; box-shadow: 4px 4px 0 0 var(--fg);
            margin-bottom: 24px; background: var(--surface);
        }
        .side-card-title { font-size: 12px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--fg-dim); margin: 0 0 12px; font-weight: 500; }
        .side-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; border-bottom: 1px solid var(--hairline); }
        .side-row:last-child { border: 0; }
        .side-row b { color: var(--accent); font-weight: 700; }

        .subscribe-row { display: flex; }
        .subscribe-input {
            flex: 1; padding: 8px 12px; border: 2px solid var(--fg); border-right: 0;
            background: var(--bg); color: var(--fg); font-family: var(--font-mono); font-size: 13px; outline: none;
        }
        .subscribe-btn {
            padding: 8px 14px; border: 2px solid var(--fg); background: var(--accent);
            color: var(--bg); font-weight: 700; cursor: pointer; font-family: var(--font-mono);
        }

        .events-empty { padding: 80px 32px; border: 2px dashed var(--rule-2); text-align: center; color: var(--fg-mute); }

        @media (max-width: 900px) {
            .events-layout { grid-template-columns: 1fr; }
            .events-page { padding: 40px 20px 0; }
        }
        @media (max-width: 640px) {
            .event-row { grid-template-columns: 72px 1fr; }
            .event-going { display: none; }
            .events-page-head { grid-template-columns: 1fr; }
        }
    </style>

    <div class="events-page">
        <div class="events-page-head">
            <div>
                <h1><span class="pr">$</span>events --upcoming</h1>
                <div class="sub">Everything on the calendar. RSVPs are free and non-binding.</div>
            </div>
            <div style="text-align: right;">
                @php
                    $totalEvents = $meetupsByMonth->flatten()->count();
                    $totalCities = $meetupsByMonth->flatten()->pluck('location')->unique()->count();
                @endphp
                <div style="color: var(--fg-mute); font-size: 12px; letter-spacing: 0.12em; text-transform: uppercase; margin-bottom: 8px;">
                    {{ $totalEvents }} event{{ $totalEvents !== 1 ? 's' : '' }} · {{ $totalCities }} {{ $totalCities === 1 ? 'city' : 'cities' }}
                </div>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <a href="#" class="btn btn-ghost" style="padding: 6px 10px; font-size: 12px;">+ ical</a>
                    <a href="#" class="btn btn-ghost" style="padding: 6px 10px; font-size: 12px;">rss</a>
                </div>
            </div>
        </div>

        <div class="events-layout">
            <main>
                @forelse ($meetupsByMonth as $month => $meetups)
                    <div class="month-head">
                        <h2>{{ strtoupper($month) }}</h2>
                        <div class="month-rule"></div>
                        <div class="month-count">{{ $meetups->count() }} event{{ $meetups->count() !== 1 ? 's' : '' }}</div>
                    </div>
                    @foreach ($meetups as $meetup)
                        <a href="/events/{{ $meetup->slug }}" class="event-row">
                            <div class="event-date">
                                <div class="day">{{ $meetup->starts_at->format('d') }}</div>
                                <div class="month-dow">{{ strtoupper($meetup->starts_at->format('M')) }} · {{ strtoupper($meetup->starts_at->format('D')) }}</div>
                            </div>
                            <div>
                                <div class="event-title-text">{{ $meetup->title }}</div>
                                <div class="event-chips">
                                    @foreach ($meetup->tags->take(3) as $tag)
                                        <span class="chip">{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                                <div class="event-sub">{{ $meetup->location }} · {{ $meetup->starts_at->format('g:i a') }}</div>
                            </div>
                            <div class="event-going">{{ $meetup->rsvps->count() }} going →</div>
                        </a>
                    @endforeach
                @empty
                    <div class="events-empty">
                        <div style="font-size: 16px; margin-bottom: 8px;">// no upcoming events <span style="display:inline-block; width:.55em; height:1em; background:var(--accent); vertical-align:-.15em;"></span></div>
                        <div style="font-size: 13px;">Want to kick things off? <a href="#host">host one yourself →</a></div>
                    </div>
                @endforelse
            </main>

            <aside>
                <div class="side-card">
                    <div class="side-card-title">// quick stats</div>
                    @php $flat = $meetupsByMonth->flatten(); @endphp
                    <div class="side-row"><span>events shown</span><b>{{ $flat->count() }}</b></div>
                    <div class="side-row"><span>people going</span><b>{{ $flat->sum(fn($m) => $m->rsvps->count()) }}</b></div>
                    <div class="side-row"><span>cities</span><b>{{ $flat->pluck('location')->unique()->count() }}</b></div>
                    <div class="side-row">
                        <span>next event</span>
                        <b>{{ $flat->first() ? $flat->first()->starts_at->diffForHumans() : '—' }}</b>
                    </div>
                </div>

                <div class="side-card">
                    <div class="side-card-title">// subscribe</div>
                    <p style="font-size: 13px; color: var(--fg-dim); margin: 0 0 12px;">One short email on Mondays. The week ahead, that's it.</p>
                    <div class="subscribe-row">
                        <input class="subscribe-input" type="email" placeholder="you@domain">
                        <button class="subscribe-btn">→</button>
                    </div>
                </div>

                <div class="side-card">
                    <div class="side-card-title">// host</div>
                    <p style="font-size: 13px; color: var(--fg-dim); margin: 0 0 12px;">Got a topic, a venue, or just a Tuesday? Submit a one-paragraph proposal.</p>
                    <a href="#" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 10px 14px; font-size: 12px;">SUBMIT EVENT →</a>
                </div>
            </aside>
        </div>

        <div style="margin-top: 0;">
            @include('layouts.app') {{-- footer rendered by layout --}}
        </div>
    </div>
</div>
```

> **Note:** The footer is rendered by the layout automatically — do not include it in the Livewire view.

- [ ] **Step 4: Add route**

In `routes/web.php`, add:

```php
use App\Livewire\EventsIndex;

Route::get('/events', EventsIndex::class);
```

- [ ] **Step 5: Smoke test**

```bash
curl -s http://518codes.test/events | grep -c "events --upcoming"
# Expected: 1
```

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/EventsIndex.php resources/views/livewire/events-index.blade.php routes/web.php
git commit -m "feat: add EventsIndex Livewire component and /events route"
```

---

## Task 4: `EventDetail` Livewire component + RSVP action

**Files:**
- Create: `app/Livewire/EventDetail.php`
- Create: `resources/views/livewire/event-detail.blade.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Write failing tests**

```bash
php artisan make:test --pest EventDetailRsvpTest
```

Replace with:

```php
<?php

use App\Enums\MeetupStatus;
use App\Models\Meetup;
use App\Models\Rsvp;
use Livewire\Livewire;
use App\Livewire\EventDetail;

test('event detail page loads for a published meetup', function () {
    $meetup = Meetup::factory()->create([
        'status' => MeetupStatus::Published,
        'starts_at' => now()->addDays(7),
    ]);

    $this->get("/events/{$meetup->slug}")->assertOk();
});

test('event detail returns 404 for a draft meetup', function () {
    $meetup = Meetup::factory()->create([
        'status' => MeetupStatus::Draft,
        'starts_at' => now()->addDays(7),
    ]);

    $this->get("/events/{$meetup->slug}")->assertNotFound();
});

test('rsvp action creates an rsvp record and sets rsvpd to true', function () {
    $meetup = Meetup::factory()->create([
        'status' => MeetupStatus::Published,
        'starts_at' => now()->addDays(7),
    ]);

    Livewire::test(EventDetail::class, ['slug' => $meetup->slug])
        ->set('name', 'Ada Tang')
        ->set('email', 'ada@example.com')
        ->call('rsvp')
        ->assertSet('rsvpd', true);

    expect(Rsvp::where('meetup_id', $meetup->id)->where('email', 'ada@example.com')->exists())->toBeTrue();
});

test('rsvp action requires name and email', function () {
    $meetup = Meetup::factory()->create([
        'status' => MeetupStatus::Published,
        'starts_at' => now()->addDays(7),
    ]);

    Livewire::test(EventDetail::class, ['slug' => $meetup->slug])
        ->set('name', '')
        ->set('email', '')
        ->call('rsvp')
        ->assertHasErrors(['name' => 'required', 'email' => 'required']);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact --filter=EventDetailRsvpTest
# Expected: 4 failed — component and route don't exist yet
```

- [ ] **Step 3: Scaffold component**

```bash
php artisan make:livewire EventDetail --no-interaction
```

- [ ] **Step 4: Write `app/Livewire/EventDetail.php`**

```php
<?php

namespace App\Livewire;

use App\Enums\MeetupStatus;
use App\Models\Meetup;
use Livewire\Attributes\Validate;
use Livewire\Component;

class EventDetail extends Component
{
    public Meetup $meetup;
    public bool $rsvpd = false;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    public function mount(string $slug): void
    {
        $this->meetup = Meetup::with(['tags', 'rsvps'])
            ->where('slug', $slug)
            ->where('status', MeetupStatus::Published)
            ->firstOrFail();
    }

    public function rsvp(): void
    {
        $this->validate();

        $this->meetup->rsvps()->create([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        $this->rsvpd = true;
        $this->meetup->load('rsvps');
    }

    public function render()
    {
        return view('livewire.event-detail')
            ->layout('layouts.app', [
                'title' => $this->meetup->title . ' · 518.codes',
            ]);
    }
}
```

- [ ] **Step 5: Write the event detail view**

Replace `resources/views/livewire/event-detail.blade.php` with:

```blade
<div>
    <style>
        .detail-page { max-width: 1200px; margin: 0 auto; padding: 40px 32px 0; }

        .crumbs { font-size: 13px; color: var(--fg-mute); margin-bottom: 24px; }
        .crumbs a { color: var(--fg-dim); text-decoration: none; }
        .crumbs a:hover { color: var(--accent); background: transparent; }
        .crumbs .sep { margin: 0 8px; color: var(--accent); }

        .detail-header {
            display: grid; grid-template-columns: 1fr 340px; gap: 48px; align-items: start;
            padding-bottom: 40px; border-bottom: 2px solid var(--fg); margin-bottom: 40px;
        }
        .detail-chips { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
        .detail-header h1 { font-size: 56px; line-height: 1.05; letter-spacing: -0.02em; margin: 0 0 16px; font-weight: 800; }
        .detail-lede { font-size: 18px; color: var(--fg-dim); line-height: 1.55; max-width: 56ch; margin: 0; }

        .manifest {
            border: 2px solid var(--fg); background: var(--surface);
            box-shadow: 4px 4px 0 0 var(--fg); font-size: 13px;
        }
        .manifest-head {
            background: var(--fg); color: var(--bg); padding: 8px 14px;
            font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; font-weight: 700;
        }
        .manifest dl { margin: 0; padding: 14px; display: grid; grid-template-columns: 72px 1fr; gap: 8px 14px; }
        .manifest dt { color: var(--fg-mute); font-size: 11px; letter-spacing: 0.08em; text-transform: uppercase; padding-top: 2px; }
        .manifest dd { margin: 0; color: var(--fg); line-height: 1.4; }
        .manifest dd .acc { color: var(--accent); }
        .manifest-actions { padding: 14px; border-top: 1px solid var(--hairline); display: flex; flex-direction: column; gap: 8px; }
        .manifest-rsvp-form { display: flex; flex-direction: column; gap: 8px; }
        .manifest-input {
            padding: 8px 12px; border: 2px solid var(--fg); background: var(--bg);
            color: var(--fg); font-family: var(--font-mono); font-size: 13px; outline: none; width: 100%;
        }
        .manifest-input:focus { border-color: var(--accent); }
        .manifest-error { font-size: 11px; color: var(--amber); margin-top: 2px; }

        .detail-body { display: grid; grid-template-columns: 1fr 340px; gap: 48px; align-items: start; }

        .detail-body main h2 { font-size: 28px; margin: 48px 0 16px; letter-spacing: -0.01em; font-weight: 700; }
        .detail-body main h2:first-child { margin-top: 0; }
        .detail-body main h2 .pr { color: var(--accent); margin-right: 8px; }
        .detail-body main p { font-size: 17px; line-height: 1.7; color: var(--fg-dim); max-width: 72ch; margin: 0 0 16px; }

        .schedule { border: 2px solid var(--fg); }
        .schedule-row { display: grid; grid-template-columns: 80px 1fr; padding: 12px 16px; border-bottom: 1px solid var(--hairline); align-items: baseline; }
        .schedule-row:last-child { border: 0; }
        .schedule-t { color: var(--accent); font-family: var(--font-display); font-size: 22px; line-height: 1; }
        .schedule-what b { display: block; font-size: 14px; margin-bottom: 2px; }
        .schedule-what span { color: var(--fg-dim); font-size: 12px; }

        .going-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 4px; margin-top: 12px; }
        .going-av {
            aspect-ratio: 1; border: 1.5px solid var(--fg-dim); background: var(--surface);
            display: flex; align-items: center; justify-content: center; font-size: 11px; color: var(--fg-dim);
        }
        .going-more { border-color: var(--accent); color: var(--accent); font-weight: 700; }

        .where-card { border: 2px solid var(--fg); padding: 18px; box-shadow: 4px 4px 0 0 var(--fg); background: var(--surface); margin-bottom: 24px; }
        .where-card-title { font-size: 12px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--fg-dim); margin: 0 0 10px; font-weight: 500; }
        .ascii-map {
            border: 2px solid var(--fg); padding: 0; margin-top: 0;
        }
        .ascii-map pre {
            margin: 0; padding: 16px; color: var(--accent); font-size: 10px; line-height: 1.1;
            background: var(--surface); border-bottom: 2px solid var(--fg);
            text-shadow: 0 0 4px rgba(94,252,141,0.3); overflow: hidden;
            font-family: var(--font-mono); white-space: pre;
        }
        .ascii-map .addr { padding: 12px 16px; font-size: 13px; color: var(--fg-dim); }
        .ascii-map .addr b { color: var(--fg); display: block; font-weight: 700; margin-bottom: 4px; }

        @media (max-width: 900px) {
            .detail-header { grid-template-columns: 1fr; }
            .detail-body { grid-template-columns: 1fr; }
            .detail-page { padding: 32px 20px 0; }
            .detail-header h1 { font-size: 40px; }
        }
    </style>

    <div class="detail-page">
        {{-- Breadcrumb --}}
        <div class="crumbs">
            <a href="/">$ 518.codes</a>
            <span class="sep">/</span>
            <a href="/events">events</a>
            <span class="sep">/</span>
            <span>{{ $meetup->slug }}</span>
        </div>

        {{-- Header --}}
        <div class="detail-header">
            <div>
                <div class="detail-chips">
                    @foreach ($meetup->tags as $tag)
                        <span class="chip chip-accent">{{ $tag->name }}</span>
                    @endforeach
                    <span class="chip">first-timer friendly</span>
                </div>
                <h1>{{ $meetup->title }}</h1>
                <p class="detail-lede">{{ Str::limit($meetup->description, 280) }}</p>
            </div>

            {{-- Manifest panel --}}
            <div class="manifest">
                <div class="manifest-head">// event.manifest</div>
                <dl>
                    <dt>when</dt>
                    <dd>
                        <span class="acc">{{ $meetup->starts_at->format('D') }}</span>,
                        {{ $meetup->starts_at->format('M j, Y') }}<br>
                        {{ $meetup->starts_at->format('g:i a') }}
                        @if ($meetup->ends_at)
                            – {{ $meetup->ends_at->format('g:i a') }}
                        @endif
                    </dd>
                    <dt>where</dt>
                    <dd>{{ $meetup->location }}</dd>
                    <dt>cost</dt>
                    <dd><span class="acc">$0</span></dd>
                    <dt>going</dt>
                    <dd><span class="acc">{{ $meetup->rsvps->count() }}</span> so far</dd>
                </dl>
                <div class="manifest-actions">
                    @if ($rsvpd)
                        <button class="btn btn-primary" disabled style="width: 100%; justify-content: center;">
                            ✓ YOU'RE GOING
                        </button>
                    @else
                        <div class="manifest-rsvp-form">
                            <div>
                                <input
                                    class="manifest-input"
                                    type="text"
                                    placeholder="your name"
                                    wire:model="name"
                                >
                                @error('name') <div class="manifest-error">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <input
                                    class="manifest-input"
                                    type="email"
                                    placeholder="your email"
                                    wire:model="email"
                                >
                                @error('email') <div class="manifest-error">{{ $message }}</div> @enderror
                            </div>
                            <button
                                class="btn btn-primary"
                                wire:click="rsvp"
                                wire:loading.attr="disabled"
                                style="width: 100%; justify-content: center;"
                            >
                                <span wire:loading.remove wire:target="rsvp">RSVP · {{ $meetup->rsvps->count() }} GOING</span>
                                <span wire:loading wire:target="rsvp">SAVING...</span>
                            </button>
                        </div>
                    @endif
                    <a href="#" class="btn btn-ghost" style="width: 100%; justify-content: center; font-size: 12px;">+ add to calendar</a>
                </div>
            </div>
        </div>

        {{-- Body --}}
        <div class="detail-body">
            <main>
                <h2><span class="pr">›</span>what to expect</h2>
                @foreach (explode("\n\n", $meetup->description) as $paragraph)
                    @if (trim($paragraph))
                        <p>{{ trim($paragraph) }}</p>
                    @endif
                @endforeach

                <h2><span class="pr">›</span>schedule</h2>
                <div class="schedule">
                    @php
                        $schedule = [
                            ['t' => $meetup->starts_at->format('g:i'), 'title' => 'Doors open', 'note' => 'Come in, get settled, grab a coffee.'],
                            ['t' => $meetup->starts_at->copy()->addMinutes(30)->format('g:i'), 'title' => 'Talks & demos', 'note' => 'Half-broken things especially welcome. No slides required.'],
                            ['t' => $meetup->starts_at->copy()->addMinutes(90)->format('g:i'), 'title' => 'Open discussion', 'note' => 'Drift between tables, ask questions, get unstuck.'],
                            ['t' => $meetup->ends_at ? $meetup->ends_at->format('g:i') : $meetup->starts_at->copy()->addHours(3)->format('g:i'), 'title' => 'Wrap up', 'note' => 'Hallway track continues wherever the group ends up.'],
                        ];
                    @endphp
                    @foreach ($schedule as $item)
                        <div class="schedule-row">
                            <div class="schedule-t">{{ $item['t'] }}</div>
                            <div class="schedule-what">
                                <b>{{ $item['title'] }}</b>
                                <span>{{ $item['note'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <h2><span class="pr">›</span>who's going</h2>
                @php
                    $rsvps = $meetup->rsvps;
                    $shown = $rsvps->take(16);
                    $overflow = $rsvps->count() - 16;
                @endphp
                @if ($rsvps->isEmpty())
                    <p style="color: var(--fg-mute); font-size: 14px;">No RSVPs yet. Be the first.</p>
                @else
                    <div class="going-grid">
                        @foreach ($shown as $rsvp)
                            <div class="going-av" title="{{ $rsvp->name }}">
                                {{ strtoupper(substr($rsvp->name, 0, 1)) }}{{ strtoupper(substr(strstr($rsvp->name, ' '), 1, 1)) }}
                            </div>
                        @endforeach
                        @if ($overflow > 0)
                            <div class="going-av going-more">+{{ $overflow }}</div>
                        @endif
                    </div>
                @endif
            </main>

            <aside>
                <div class="where-card">
                    <div class="where-card-title">// where</div>
                    <div style="font-size: 14px; color: var(--fg-dim); line-height: 1.6;">
                        {{ $meetup->location }}
                    </div>
                </div>

                <div class="ascii-map">
                    <pre aria-hidden="true">   ─────────────────────────────────
              MAIN ST

    ●═════[ {{ Str::limit($meetup->location, 16) }} ]═════●
    │        · venue ·               │
    ●─────────────────────────────────●

        BROADWAY
   ─────────────────────────────────</pre>
                    <div class="addr">
                        <b>{{ $meetup->location }}</b>
                        {{ $meetup->starts_at->format('D, M j · g:i a') }}
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
```

- [ ] **Step 6: Add route**

In `routes/web.php`, add:

```php
use App\Livewire\EventDetail;

Route::get('/events/{slug}', EventDetail::class);
```

- [ ] **Step 7: Run all tests**

```bash
php artisan test --compact --filter=EventDetailRsvpTest
# Expected: 4 passed
```

- [ ] **Step 8: Commit**

```bash
git add app/Livewire/EventDetail.php resources/views/livewire/event-detail.blade.php routes/web.php tests/Feature/EventDetailRsvpTest.php
git commit -m "feat: add EventDetail Livewire component with RSVP action and /events/{slug} route"
```

---

## Task 5: Smoke tests + final polish

**Files:**
- Modify: `tests/Feature/ExampleTest.php`

- [ ] **Step 1: Add smoke tests for all public routes**

Replace `tests/Feature/ExampleTest.php` with:

```php
<?php

use App\Enums\MeetupStatus;
use App\Models\Meetup;

test('home page returns 200', function () {
    $this->get('/')->assertOk();
});

test('events listing page returns 200', function () {
    $this->get('/events')->assertOk();
});

test('event detail returns 200 for a published meetup', function () {
    $meetup = Meetup::factory()->create([
        'status' => MeetupStatus::Published,
        'starts_at' => now()->addDays(5),
    ]);

    $this->get("/events/{$meetup->slug}")->assertOk();
});

test('event detail returns 404 for unknown slug', function () {
    $this->get('/events/does-not-exist')->assertNotFound();
});
```

- [ ] **Step 2: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 3: Run all tests**

```bash
php artisan test --compact
# Expected: all green
```

- [ ] **Step 4: Build assets**

```bash
npm run build
```

- [ ] **Step 5: Verify in browser**

```bash
open http://518codes.test/events
open http://518codes.test/events/$(php artisan tinker --execute "use App\Enums\MeetupStatus; echo App\Models\Meetup::where('status', MeetupStatus::Published)->first()->slug ?? 'none';")
```

- [ ] **Step 6: Final commit**

```bash
git add tests/Feature/ExampleTest.php
git commit -m "test: add smoke tests for home, events listing, and event detail pages"
```
