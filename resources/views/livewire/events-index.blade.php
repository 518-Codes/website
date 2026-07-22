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
        .event-row:hover { background: var(--surface); color: var(--fg); padding-left: 12px; padding-right: 12px; margin: 0 -12px; }
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
                <div style="color: var(--fg-mute); font-size: 12px; letter-spacing: 0.12em; text-transform: uppercase;">
                    {{ $totalEvents }} event{{ $totalEvents !== 1 ? 's' : '' }} · {{ $totalCities }} {{ $totalCities === 1 ? 'city' : 'cities' }}
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
                        <div style="font-size: 13px;">Want to kick things off? <a href="{{ route('host') }}">host one yourself →</a></div>
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
                        <b>{{ $flat->first() ? ($flat->first()->isInProgress() ? 'happening now' : $flat->first()->starts_at->diffForHumans()) : '—' }}</b>
                    </div>
                </div>

                <div class="side-card">
                    <div class="side-card-title">// discord</div>
                    <p style="font-size: 13px; color: var(--fg-dim); margin: 0 0 12px;">We post events and hang out in Discord. Come say hi.</p>
                    <a href="{{ config('community.discord_url') }}" target="_blank" rel="noopener" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 10px 14px; font-size: 12px;">JOIN THE DISCORD →</a>
                </div>

                <div class="side-card">
                    <div class="side-card-title">// host</div>
                    <p style="font-size: 13px; color: var(--fg-dim); margin: 0 0 12px;">Got a topic, a venue, or just a Tuesday? Submit a one-paragraph proposal.</p>
                    <a href="{{ route('host') }}" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 10px 14px; font-size: 12px;">SUBMIT EVENT →</a>
                </div>
            </aside>
        </div>
    </div>
</div>
