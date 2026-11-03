@extends('layouts.app')

@section('title', '518.codes — developers in New York\'s Capital Region')

@section('styles')
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
        .ticker-track { display: inline-block; padding-left: 100%; animation: tick var(--ticker-duration, 32s) linear infinite; }
        @keyframes tick { to { transform: translateX(-100%); } }
        @media (prefers-reduced-motion: reduce) { .ticker-track { animation: none; padding-left: 0; } }

        /* Hero */
        .hero { padding: 56px 32px 64px; border-bottom: 4px solid var(--fg); position: relative; overflow: hidden; }
        .hero-inner { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 7fr 5fr; gap: 48px; align-items: end; }
        .hero-display {
            font-weight: 800;
            font-size: clamp(40px, 8vw, 168px);
            line-height: 0.9;
            letter-spacing: -0.04em;
            margin: 0;
        }
        .hero-display .l1, .hero-display .l3 { display: block; color: var(--fg); white-space: nowrap; }
        .hero-display .l2 { display: block; color: var(--accent); white-space: nowrap; }
        .hero-tag { font-size: 16px; color: var(--fg-dim); margin: 24px 0 0; max-width: 38ch; line-height: 1.5; }
        .hero-cta-row { display: flex; gap: 16px; margin-top: 32px; flex-wrap: wrap; }

        .meta-card {
            min-width: 400px;
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

        .featured-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 32px; margin-bottom: 48px; }

        .schedule-card {
            border: 2px solid var(--fg); padding: 20px; background: var(--surface);
        }
        .schedule-card ul { list-style: none; padding: 0; margin: 12px 0 0; color: var(--fg-dim); font-size: 14px; line-height: 1.9; }
        .schedule-card li .time { color: var(--accent); }

        /* Event rows */
        .event-row {
            display: grid; grid-template-columns: 96px minmax(0, 1fr) auto;
            gap: 20px; align-items: center;
            padding: 22px 0; border-bottom: 1px solid var(--hairline);
            color: inherit; text-decoration: none;
            transition: background 120ms;
        }
        .event-row:hover { background: var(--surface); color: var(--fg); padding-left: 12px; padding-right: 12px; margin: 0 -12px; }
        .event-date { font-family: var(--font-display); color: var(--accent); line-height: 1; }
        .event-date .day { font-size: 40px; line-height: 1; }
        .event-date .month-dow { font-size: 14px; letter-spacing: 0.1em; }
        .event-title { font-weight: 700; font-size: 17px; }
        .event-sub { font-size: 12px; color: var(--fg-mute); letter-spacing: 0.04em; margin-top: 4px; }
        .event-chips { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 4px; }
        .event-going { color: var(--accent); font-size: 13px; white-space: nowrap; }

        /* About */
        .about-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 64px; align-items: start; }
        .about-grid p { font-size: 16px; line-height: 1.65; color: var(--fg-dim); max-width: 52ch; margin: 0 0 16px; }
        .about-grid p:last-of-type { color: var(--fg); }
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .stat-card { border: 2px solid var(--fg); padding: 18px; box-shadow: 4px 4px 0 0 var(--fg); }
        .stat-card .stat-n { font-family: var(--font-display); font-size: 56px; color: var(--accent); line-height: 1; }
        .stat-card .stat-l { font-size: 12px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--fg-dim); margin-top: 6px; }

        /* Founders */
        .founders { margin-top: 56px; }
        .founders-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-top: 14px; }
        .founder-card { border: 2px solid var(--fg); padding: 18px; box-shadow: var(--shadow-2); }
        .founder-avatar {
            width: 56px; height: 56px; border: 2px solid var(--fg); background: var(--surface-2);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 20px; color: var(--accent); margin-bottom: 14px;
        }
        .founder-name { font-weight: 700; font-size: 15px; margin-bottom: 4px; }
        .founder-link { font-size: 13px; }

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

        /* Empty state */
        .empty { padding: 48px 0; color: var(--fg-mute); font-size: 14px; }
        .empty code { background: var(--surface-2); border: 1px solid var(--hairline); padding: 1px 6px; color: var(--accent); }

        /* Responsive */
        @media (max-width: 900px) {
            .hero-inner { grid-template-columns: 1fr; }
            .featured-grid { grid-template-columns: 1fr; }
            .meta-card { display: none; }
            .about-grid { grid-template-columns: 1fr; gap: 32px; }
            .founders-grid { grid-template-columns: 1fr 1fr; }
            .cta-block { grid-template-columns: 1fr; }
            .hero { padding: 40px 20px 48px; }
            .section { padding: 48px 20px; }
        }
        @media (max-width: 640px) {
            .event-row { grid-template-columns: 72px minmax(0, 1fr); }
            .event-going { display: none; }
        }
@endsection

@section('content')
{{-- Hero --}}
<section class="hero" id="home">
    <div class="scanlines" aria-hidden="true"></div>
    <div class="hero-inner" style="position: relative;">
        <div>
            <div class="kicker">capital region · est 2026</div>
            <h1 class="hero-display" style="margin-top: 24px;">
                <span class="l1">YOU'RE NOT</span>
                <span class="l3">THE ONLY DEV</span>
                <span class="l2">IN THE 518.</span>
            </h1>
            <p class="hero-tag">
                A weekly happy hour and a monthly build night for developers in the Capital Region.
                Free, and open to anyone who's into code.
            </p>
            <div class="hero-cta-row">
                <a href="#events" class="btn btn-primary">SEE WHAT'S COMING UP →</a>
                <a href="{{ route('host') }}" class="btn btn-secondary">› host an event</a>
            </div>
        </div>
        <div class="meta-card">
            <div class="kicker">// live · capital region</div>
            <div style="margin-top: 14px;">
                @if($nextMeetup)
                    <div class="row">
                        <span>next event</span>
                        <b>{{ $nextMeetup->isInProgress() ? 'happening now' : $nextMeetup->starts_at->diffForHumans() }}</b>
                    </div>
                    <div class="row">
                        <span>events coming up</span>
                        <b>{{ $upcomingMeetups->count() + 1 }}</b>
                    </div>
                @else
                    <div class="row"><span>next event</span><b>TBA</b></div>
                @endif
                <div class="row"><span>happy hour</span><b>wednesdays</b></div>
                <div class="row"><span>build night</span><b>monthly</b></div>
            </div>
        </div>
    </div>
</section>

{{-- Ticker --}}
@if($nextMeetup || $upcomingMeetups->count())
@php
    $allMeetups = ($nextMeetup ? collect([$nextMeetup])->merge($upcomingMeetups) : $upcomingMeetups)->take(3);
    $tickerItems = $allMeetups->map(fn($m) => strtoupper($m->title) . ' · ' . strtoupper($m->starts_at->format('M j')) . ' · ' . strtoupper($m->location))->implode('  /  ');
    $tickerLine = $tickerItems . '  /  ';
    $tickerDuration = max(30, round(strlen($tickerLine) * 0.15)) . 's';
@endphp
<div class="ticker" aria-hidden="true">
    <div class="ticker-track" style="--ticker-duration: {{ $tickerDuration }}">
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

        <div class="featured-grid">
            <div class="featured-block">
                <div class="kicker">{{ $nextMeetup->isInProgress() ? 'HAPPENING NOW' : 'NEXT UP · '.strtoupper($nextMeetup->starts_at->diffForHumans()) }}</div>
                <h3>{{ $nextMeetup->title }}</h3>
                <div class="featured-meta">
                    {{ $nextMeetup->starts_at->format('D, M j') }} · {{ $nextMeetup->starts_at->format('g:i a') }}
                    @if($nextMeetup->ends_at)
                        – {{ $nextMeetup->ends_at->format('g:i a') }}
                    @endif
                    · {{ $nextMeetup->location }}
                </div>
                <p class="featured-desc">{{ Str::limit($nextMeetup->plainDescription(), 200) }}</p>
                @if($nextMeetup->tags->isNotEmpty())
                    <div class="event-chips" style="margin-bottom: 16px;">
                        @foreach($nextMeetup->tags as $tag)
                            <span class="chip chip-accent">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif
                <div class="featured-footer">
                    <a href="{{ route('events.show', $nextMeetup->slug) }}" class="btn btn-primary">
                        RSVP · {{ $nextMeetup->rsvps->count() }} GOING
                    </a>
                    <span class="featured-cap">free · all levels welcome</span>
                </div>
            </div>

            <div class="schedule-card">
                <div class="kicker kicker-mute">// the regulars</div>
                <ul>
                    <li><span class="time">weekly</span> &nbsp;happy hour, wednesdays</li>
                    <li><span class="time">monthly</span> &nbsp;build night: pizza, laptops</li>
                    <li><span class="time">soon</span> &nbsp;talks</li>
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
            <a href="{{ route('events.index') }}" style="color: var(--accent); font-size: 13px; text-decoration: none;">view all →</a>
        </div>
        <div>
            @foreach($upcomingMeetups as $meetup)
            <a href="{{ route('events.show', $meetup->slug) }}" class="event-row">
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
            <h2><span style="color: var(--accent);">›</span> events</h2>
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
            <div class="meta-label">est. summer 2026</div>
        </div>
        <div class="about-grid">
            <div>
                <p>
                    518.codes started at a Startup Tech Valley event in the summer of 2026. Each of us
                    figured there weren't many developers up here, and most tech meetups we knew of were in
                    New York City. It turns out there are plenty of us. We just didn't have a place to meet.
                </p>
                <p>
                    So now there's a happy hour every Wednesday, often at Ilium on Fulton Street in Troy, and a
                    build night once a month. Build nights are pizza and side projects, though mostly we end up
                    talking and riffing on ideas. Talks are coming.
                </p>
                <p>
                    Our members so far include people building apps with AI, remote engineers who miss having
                    coworkers to talk to, beginners, and at least one Linux distro maintainer. If you're into
                    code, you'll fit in.
                </p>
                <p>
                    <span style="color: var(--accent);">›</span> Come by on a Wednesday. Bring a friend.
                </p>
                <div class="city-chips">
                    @foreach(config('cities') as $city)
                        <span class="city-chip">{{ $city }}</span>
                    @endforeach
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-n">{{ $stats['events_hosted'] }}</div>
                    <div class="stat-l">events</div>
                </div>
                <div class="stat-card">
                    <div class="stat-n">{{ $stats['rsvps_total'] }}</div>
                    <div class="stat-l">total rsvps</div>
                </div>
                <div class="stat-card">
                    <div class="stat-n">{{ count(config('cities')) }}</div>
                    <div class="stat-l">cities</div>
                </div>
                <div class="stat-card">
                    <div class="stat-n">$0</div>
                    <div class="stat-l">cost to show up</div>
                </div>
            </div>
        </div>
        <div class="founders">
            <div class="kicker kicker-mute">// founders</div>
            <div class="founders-grid">
                @foreach([
                    ['name' => 'Luigi Battaglioli', 'site' => '51eat.co'],
                    ['name' => 'Garret Premo', 'site' => 'premo.cloud'],
                    ['name' => 'Frank Matranga', 'site' => 'matrangacode.works'],
                    ['name' => 'Ben Clouser', 'site' => 'toradex.com'],
                ] as $founder)
                    <div class="founder-card">
                        <div class="founder-avatar" aria-hidden="true">{{ collect(explode(' ', $founder['name']))->map(fn ($part) => $part[0])->implode('') }}</div>
                        <div class="founder-name">{{ $founder['name'] }}</div>
                        <a href="https://{{ $founder['site'] }}" class="founder-link" target="_blank" rel="noopener">{{ $founder['site'] }}</a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@include('partials.region-sandbox')

{{-- Host CTA --}}
<div class="section-wrap" id="host">
    <div class="section">
        <div class="cta-block">
            <div>
                <div class="kicker" style="margin-bottom: 8px;">host an event</div>
                <h3>Want to run something?</h3>
                <p>
                    If you want to give a talk or have a space for a build night, send us a short pitch.
                    We'll work out the details with you and get it on the calendar.
                </p>
            </div>
            <a href="{{ route('host') }}" class="btn btn-primary">SUBMIT AN EVENT →</a>
        </div>
    </div>
</div>
@endsection
