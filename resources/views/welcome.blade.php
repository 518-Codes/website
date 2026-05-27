<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>518.codes — meetups for people who write code in the 518</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;700;800&family=VT323&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* 518.codes design tokens */
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
        .nav-brand {
            font-weight: 800; font-size: 18px; letter-spacing: -0.02em;
            text-decoration: none; color: inherit;
        }
        .nav-brand .dollar { color: var(--accent); margin-right: 4px; }
        .nav-brand .dot { color: var(--accent); }
        .nav-links {
            display: flex; gap: 28px; list-style: none; padding: 0; margin: 0; font-size: 14px;
        }
        .nav-links a {
            color: var(--fg-dim); text-decoration: none;
            border-bottom: 2px solid transparent; padding-bottom: 2px;
        }
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

        /* Kicker label */
        .kicker {
            font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase;
            color: var(--accent); font-weight: 600;
        }
        .kicker-mute { color: var(--fg-mute); }

        /* Chip */
        .chip {
            display: inline-flex; align-items: center;
            padding: 2px 8px; border: 1.5px solid currentColor;
            font-size: 11px; letter-spacing: 0.04em; line-height: 1.4;
            color: var(--fg-dim);
        }
        .chip-accent { color: var(--accent); }
        .chip-amber { color: var(--amber); }
        .chip-cyan { color: var(--cyan); }
        .chip-magenta { color: var(--magenta); }

        /* Scanlines */
        .scanlines {
            position: absolute; inset: 0; pointer-events: none;
            background-image: repeating-linear-gradient(0deg, rgba(94,252,141,0.04) 0 1px, transparent 1px 3px);
            mix-blend-mode: screen;
        }

        /* Ticker */
        .ticker {
            background: var(--accent); color: var(--bg);
            padding: 14px 0; border-bottom: 4px solid var(--fg);
            overflow: hidden; white-space: nowrap;
            font-weight: 700; font-size: 15px; letter-spacing: 0.06em;
        }
        .ticker-track { display: inline-block; padding-left: 100%; animation: tick 32s linear infinite; }
        @keyframes tick { to { transform: translateX(-100%); } }
        @media (prefers-reduced-motion: reduce) { .ticker-track { animation: none; padding-left: 0; } }

        /* Hero */
        .hero { padding: 56px 32px 64px; border-bottom: 4px solid var(--fg); position: relative; overflow: hidden; }
        .hero-inner { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 7fr 5fr; gap: 48px; align-items: end; }
        .hero-display {
            font-weight: 800;
            font-size: clamp(64px, 12vw, 168px);
            line-height: 0.86;
            letter-spacing: -0.04em;
            margin: 0;
        }
        .hero-display .l1, .hero-display .l3 { display: block; color: var(--fg); }
        .hero-display .l2 { display: block; color: var(--accent); }
        .hero-tag { font-size: 16px; color: var(--fg-dim); margin: 24px 0 0; max-width: 38ch; line-height: 1.5; }
        .hero-cta-row { display: flex; gap: 16px; margin-top: 32px; flex-wrap: wrap; }

        .meta-card {
            border: 4px solid var(--fg);
            box-shadow: 8px 8px 0 0 var(--accent);
            padding: 24px;
            background: var(--surface);
        }
        .meta-card .row {
            display: flex; justify-content: space-between;
            padding: 10px 0; border-bottom: 1px solid var(--hairline); font-size: 13px;
        }
        .meta-card .row:last-child { border: 0; }
        .meta-card .row b { color: var(--accent); font-weight: 700; }

        /* Sections */
        .section { padding: 64px 32px; max-width: 1200px; margin: 0 auto; }
        .section + .section { border-top: 1px solid var(--hairline); }
        .section-wrap { border-top: 1px solid var(--hairline); }
        .section-head {
            display: flex; align-items: baseline; justify-content: space-between;
            margin-bottom: 28px; gap: 24px;
        }
        .section-head h2 { font-size: 28px; margin: 0; font-weight: 700; }
        .section-head .meta-label { color: var(--fg-mute); font-size: 12px; letter-spacing: 0.12em; text-transform: uppercase; }

        /* Featured event block */
        .featured-block {
            border: 4px solid var(--accent);
            box-shadow: 6px 6px 0 0 var(--accent);
            padding: 28px;
        }
        .featured-block h3 { font-size: 32px; margin: 12px 0 6px; line-height: 1.1; font-weight: 700; }
        .featured-meta { color: var(--fg-dim); margin-bottom: 18px; font-size: 14px; }
        .featured-desc { color: var(--fg-dim); max-width: 52ch; margin: 0 0 20px; font-size: 14px; line-height: 1.65; }
        .featured-footer { display: flex; gap: 12px; align-items: center; }
        .featured-cap { font-size: 12px; color: var(--fg-mute); }

        .schedule-card {
            border: 2px solid var(--fg); padding: 20px; background: var(--surface);
        }
        .schedule-card ul { list-style: none; padding: 0; margin: 12px 0 0; color: var(--fg-dim); font-size: 14px; line-height: 1.9; }
        .schedule-card li .time { color: var(--accent); }

        /* Event rows */
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
        .event-title { font-weight: 700; font-size: 17px; }
        .event-sub { font-size: 12px; color: var(--fg-mute); letter-spacing: 0.04em; margin-top: 4px; }
        .event-chips { display: flex; gap: 8px; align-items: center; margin-top: 4px; }
        .event-going { color: var(--accent); font-size: 13px; white-space: nowrap; }

        /* About */
        .about-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 64px; align-items: start; }
        .about-grid p { font-size: 16px; line-height: 1.65; color: var(--fg-dim); max-width: 52ch; margin: 0 0 16px; }
        .about-grid p:last-of-type { color: var(--fg); }
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .stat-card { border: 2px solid var(--fg); padding: 18px; box-shadow: 4px 4px 0 0 var(--fg); }
        .stat-card .stat-n { font-family: var(--font-display); font-size: 56px; color: var(--accent); line-height: 1; }
        .stat-card .stat-l { font-size: 12px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--fg-dim); margin-top: 6px; }

        /* City chips */
        .city-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 18px; }
        .city-chip { padding: 3px 9px; border: 1.5px solid var(--fg-mute); font-size: 12px; color: var(--fg-dim); letter-spacing: 0.04em; }

        /* CTA block */
        .cta-block {
            border: 4px solid var(--accent);
            box-shadow: 8px 8px 0 0 var(--accent);
            padding: 40px;
            display: grid; grid-template-columns: 1fr auto; gap: 24px; align-items: center;
        }
        .cta-block h3 { font-size: 28px; margin: 0 0 8px; font-weight: 700; }
        .cta-block p { color: var(--fg-dim); margin: 0; max-width: 48ch; font-size: 15px; }

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

        /* Empty state */
        .empty { padding: 48px 0; color: var(--fg-mute); font-size: 14px; }
        .empty code { background: var(--surface-2); border: 1px solid var(--hairline); padding: 1px 6px; color: var(--accent); }

        /* Responsive */
        @media (max-width: 900px) {
            .hero-inner { grid-template-columns: 1fr; }
            .meta-card { display: none; }
            .about-grid { grid-template-columns: 1fr; gap: 32px; }
            .cta-block { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 24px; }
            .nav { padding: 16px 20px; }
            .hero { padding: 40px 20px 48px; }
            .section { padding: 48px 20px; }
        }
        @media (max-width: 640px) {
            .nav-links { display: none; }
            .footer-grid { grid-template-columns: 1fr; }
            .event-row { grid-template-columns: 72px 1fr; }
            .event-going { display: none; }
        }
    </style>
</head>
<body>

{{-- Nav --}}
<nav class="nav">
    <a href="/" class="nav-brand">
        <span class="dollar">$</span>518<span class="dot">.</span>codes
    </a>
    <ul class="nav-links">
        <li><a href="#events" class="active">events</a></li>
        <li><a href="#about">about</a></li>
        <li><a href="#host">host</a></li>
    </ul>
    <a href="#subscribe" class="btn btn-primary" style="padding: 8px 16px; font-size: 12px; letter-spacing: 0.12em;">
        SUBSCRIBE →
    </a>
</nav>

{{-- Hero --}}
<section class="hero" id="home">
    <div class="scanlines" aria-hidden="true"></div>
    <div class="hero-inner" style="position: relative;">
        <div>
            <div class="kicker">capital region · est 2024 · volunteer-run</div>
            <h1 class="hero-display" style="margin-top: 24px;">
                <span class="l1">SHOW UP.</span>
                <span class="l2">WRITE CODE.</span>
                <span class="l3">SAY HI.</span>
            </h1>
            <p class="hero-tag">
                Meetups, demo nights, and study groups for people who write code in the 518.
                Free, volunteer-run, neighborhood-scale. First-timers always welcome.
            </p>
            <div class="hero-cta-row">
                <a href="#events" class="btn btn-primary">SEE WHAT'S COMING UP →</a>
                <a href="#host" class="btn btn-secondary">› host an event</a>
            </div>
        </div>
        <div class="meta-card">
            <div class="kicker">// live · capital region</div>
            <div style="margin-top: 14px;">
                @if($nextMeetup)
                    <div class="row">
                        <span>next event</span>
                        <b>{{ $nextMeetup->starts_at->diffForHumans() }}</b>
                    </div>
                    <div class="row">
                        <span>events coming up</span>
                        <b>{{ $upcomingMeetups->count() + 1 }}</b>
                    </div>
                @else
                    <div class="row"><span>next event</span><b>TBA</b></div>
                @endif
                <div class="row"><span>cities</span><b>12</b></div>
                <div class="row"><span>people coming</span><b>{{ $stats['rsvps_total'] }}</b></div>
                <div class="row"><span>cost to show up</span><b>$0</b></div>
            </div>
        </div>
    </div>
</section>

{{-- Ticker --}}
@if($nextMeetup || $upcomingMeetups->count())
<div class="ticker" aria-hidden="true">
    <div class="ticker-track">
        @php
            $allMeetups = $nextMeetup ? collect([$nextMeetup])->merge($upcomingMeetups) : $upcomingMeetups;
            $tickerItems = $allMeetups->map(fn($m) => strtoupper($m->title) . ' · ' . strtoupper($m->starts_at->format('M j')) . ' · ' . strtoupper($m->location))->implode('  /  ');
            $tickerLine = $tickerItems . '  /  ';
        @endphp
        {{ str_repeat($tickerLine, 3) }}
    </div>
</div>
@endif

{{-- Events --}}
<div class="section-wrap" id="events">
    <div class="section">
        @if($nextMeetup)
        {{-- Featured next event --}}
        <div class="section-head">
            <h2><span style="color: var(--accent);">›</span> upcoming</h2>
            <div class="meta-label">{{ now()->format('Y-m-d · H:i T') }}</div>
        </div>

        <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 32px; margin-bottom: 48px;">
            <div class="featured-block">
                <div class="kicker">NEXT UP · {{ strtoupper($nextMeetup->starts_at->diffForHumans()) }}</div>
                <h3>{{ $nextMeetup->title }}</h3>
                <div class="featured-meta">
                    {{ $nextMeetup->starts_at->format('D, M j') }} · {{ $nextMeetup->starts_at->format('g:i a') }}
                    @if($nextMeetup->ends_at)
                        – {{ $nextMeetup->ends_at->format('g:i a') }}
                    @endif
                    · {{ $nextMeetup->location }}
                </div>
                <p class="featured-desc">{{ Str::limit($nextMeetup->description, 200) }}</p>
                @if($nextMeetup->tags->isNotEmpty())
                    <div class="event-chips" style="margin-bottom: 16px;">
                        @foreach($nextMeetup->tags as $tag)
                            <span class="chip chip-accent">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif
                <div class="featured-footer">
                    <a href="#" class="btn btn-primary">
                        RSVP · {{ $nextMeetup->rsvps->count() }} GOING
                    </a>
                    <span class="featured-cap">free · all levels welcome</span>
                </div>
            </div>

            <div class="schedule-card">
                <div class="kicker kicker-mute">// how it runs</div>
                <ul>
                    <li><span class="time">6:30</span> &nbsp;doors open</li>
                    <li><span class="time">7:00</span> &nbsp;talks &amp; demos</li>
                    <li><span class="time">8:00</span> &nbsp;open discussion</li>
                    <li><span class="time">9:00</span> &nbsp;hallway track</li>
                </ul>
                <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--hairline);">
                    <div class="kicker kicker-mute" style="margin-bottom: 8px;">// where</div>
                    <div style="font-size: 14px; color: var(--fg-dim); line-height: 1.6;">
                        {{ $nextMeetup->location }}
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Calendar list --}}
        @if($upcomingMeetups->count())
        <div class="section-head">
            <h2><span style="color: var(--accent);">›</span> on the calendar</h2>
            <a href="#" style="color: var(--accent); font-size: 13px; text-decoration: none;">view all →</a>
        </div>
        <div>
            @foreach($upcomingMeetups as $meetup)
            <a href="#" class="event-row">
                <div class="event-date">
                    <div class="day">{{ $meetup->starts_at->format('d') }}</div>
                    <div class="month-dow">{{ strtoupper($meetup->starts_at->format('M')) }} · {{ strtoupper($meetup->starts_at->format('D')) }}</div>
                </div>
                <div>
                    <div class="event-chips">
                        <span class="event-title">{{ $meetup->title }}</span>
                        @foreach($meetup->tags->take(2) as $tag)
                            <span class="chip">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                    <div class="event-sub">
                        {{ $meetup->location }} · {{ $meetup->starts_at->format('g:i a') }}
                    </div>
                </div>
                <div class="event-going">
                    {{ $meetup->rsvps->count() }} going →
                </div>
            </a>
            @endforeach
        </div>
        @elseif(!$nextMeetup)
        <div class="empty">
            <div class="kicker" style="margin-bottom: 12px;">›</div>
            Nothing on the calendar yet. Want to kick things off?
            <code>→ <a href="#host">submit an event</a></code>
        </div>
        @endif
    </div>
</div>

{{-- About --}}
<div class="section-wrap" id="about">
    <div class="section">
        <div class="section-head">
            <h2><span style="color: var(--accent);">›</span> about</h2>
            <div class="meta-label">est. 2024 · 0 sponsors</div>
        </div>
        <div class="about-grid">
            <div>
                <p>
                    518.codes is a community of developers, designers, students, and tinkerers who happen to
                    live in the same chunk of upstate New York. We started with a Postgres meetup at a brewery
                    and kept showing up because the conversations kept being good.
                </p>
                <p>
                    We are not a conference. We don't have sponsors, swag, or a stage. We have a calendar and
                    a small group of people who take turns picking a date and a room.
                    Anyone can host. Everyone is welcome — junior, senior, between jobs, between languages.
                </p>
                <p>
                    <span style="color: var(--accent);">›</span> Show up. Say hi. Bring a friend.
                </p>
                <div class="city-chips">
                    @foreach(['Albany', 'Troy', 'Schenectady', 'Saratoga Springs', 'Clifton Park', 'Cohoes', 'Watervliet', 'Rensselaer', 'Delmar', 'Hudson', 'Glens Falls', 'Amsterdam'] as $city)
                        <span class="city-chip">{{ $city }}</span>
                    @endforeach
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-n">{{ $stats['events_hosted'] }}</div>
                    <div class="stat-l">events hosted</div>
                </div>
                <div class="stat-card">
                    <div class="stat-n">{{ $stats['rsvps_total'] }}</div>
                    <div class="stat-l">total rsvps</div>
                </div>
                <div class="stat-card">
                    <div class="stat-n">12</div>
                    <div class="stat-l">cities reached</div>
                </div>
                <div class="stat-card">
                    <div class="stat-n">$0</div>
                    <div class="stat-l">cost to show up</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Host CTA --}}
<div class="section-wrap" id="host">
    <div class="section">
        <div class="cta-block">
            <div>
                <div class="kicker" style="margin-bottom: 8px;">host an event</div>
                <h3>Got a topic, a venue, or just a Tuesday?</h3>
                <p>
                    Submit a one-paragraph proposal. We help with logistics, copy, and getting the word out.
                    You pick the date, the room, and what you want to talk about. We bring the people.
                </p>
            </div>
            <a href="#" class="btn btn-primary">SUBMIT AN EVENT →</a>
        </div>
    </div>
</div>

{{-- Footer --}}
<footer class="footer" id="subscribe">
    <div class="footer-rule" aria-hidden="true">
        {{ str_repeat('─', 400) }}
    </div>
    <div class="footer-grid">
        <div>
            <div class="nav-brand" style="font-size: 20px; display: inline-block;">
                <span class="dollar" style="color: var(--accent); margin-right: 4px;">$</span>518<span class="dot" style="color: var(--accent);">.</span>codes
            </div>
            <p class="footer-desc">
                A community of software developers in New York's Capital Region.
                Free, volunteer-run, and always open to first-timers.
            </p>
        </div>
        <div>
            <div class="footer-col-title">community</div>
            @foreach(['events', 'host an event', 'code of conduct', 'about'] as $link)
                <a href="#" class="footer-link">› {{ $link }}</a>
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

</body>
</html>
