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
