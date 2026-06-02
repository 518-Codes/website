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
        .prose { font-size: 17px; line-height: 1.7; color: var(--fg-dim); max-width: 72ch; }
        .prose > * + * { margin-top: 16px; }
        .prose p { margin: 0; }
        .prose strong, .prose b { color: var(--fg); font-weight: 700; }
        .prose em, .prose i { font-style: italic; }
        .prose a { color: var(--accent); text-decoration: underline; text-underline-offset: 3px; }
        .prose a:hover { background: var(--accent); color: var(--bg); }
        .prose h1, .prose h2, .prose h3 { color: var(--fg); font-weight: 700; line-height: 1.2; margin: 32px 0 8px; }
        .prose h1 { font-size: 28px; }
        .prose h2 { font-size: 22px; }
        .prose h3 { font-size: 18px; }
        .prose ul, .prose ol { padding-left: 20px; margin-left: 16px; }
        .prose ul { list-style: none; }
        .prose ul li { padding-left: 18px; position: relative; margin-bottom: 6px; }
        .prose ul li::before { content: '›'; position: absolute; left: 0; color: var(--accent); font-weight: 700; }
        .prose ol { list-style: decimal; }
        .prose ol li { margin-bottom: 6px; padding-left: 4px; }
        .prose blockquote {
            border-left: 3px solid var(--accent); margin: 0;
            padding: 8px 0 8px 20px; color: var(--fg-dim);
            font-style: italic;
        }
        .prose code {
            font-family: var(--font-mono); font-size: 13px;
            background: var(--surface-2); border: 1px solid var(--hairline);
            color: var(--accent); padding: 1px 6px;
        }
        .prose pre {
            background: var(--surface); border: 2px solid var(--fg);
            padding: 16px 20px; overflow-x: auto;
            box-shadow: 4px 4px 0 0 var(--fg); margin-bottom: 24px;
        }
        .prose pre code {
            background: none; border: none; padding: 0;
            color: var(--accent); font-size: 13px; line-height: 1.6;
        }
        .prose table { border-collapse: collapse; width: 100%; border: 2px solid var(--fg); font-size: 13px; box-shadow: 4px 4px 0 0 var(--fg); }
        .prose thead th {
            background: var(--fg); color: var(--bg); padding: 6px 14px; text-align: left;
            font-size: 10px; letter-spacing: 0.12em; text-transform: uppercase; font-weight: 700;
        }
        .prose tbody tr { border-bottom: 1px solid var(--hairline); }
        .prose tbody tr:last-child { border-bottom: 0; }
        .prose tbody td { padding: 9px 14px; color: var(--fg); }
        .prose tbody tr:hover { background: var(--surface); }

        .schedule { border: 2px solid var(--fg); }
        .schedule-row { display: grid; grid-template-columns: 80px 1fr; padding: 12px 16px; border-bottom: 1px solid var(--hairline); align-items: baseline; }
        .schedule-row:last-child { border: 0; }
        .schedule-t { color: var(--accent); font-family: var(--font-display); font-size: 22px; line-height: 1; }
        .schedule-what b { display: block; font-size: 14px; margin-bottom: 2px; }
        .schedule-what span { color: var(--fg-dim); font-size: 12px; }

        .roster { border: 2px solid var(--fg); margin-top: 12px; }
        .roster-header {
            display: grid; grid-template-columns: 36px 48px 1fr 120px;
            padding: 6px 14px; background: var(--fg); color: var(--bg);
            font-size: 10px; letter-spacing: 0.12em; text-transform: uppercase; font-weight: 700;
            gap: 12px;
        }
        .roster-row {
            display: grid; grid-template-columns: 36px 48px 1fr 120px;
            padding: 9px 14px; border-bottom: 1px solid var(--hairline);
            align-items: center; gap: 12px; font-size: 13px;
        }
        .roster-row:last-child { border-bottom: 0; }
        .roster-idx { color: var(--fg-mute); font-size: 11px; font-family: var(--font-display); }
        .roster-badge {
            font-size: 11px; font-weight: 700; color: var(--bg); background: var(--accent);
            padding: 1px 6px; letter-spacing: 0.04em; display: inline-block;
        }
        .roster-name { color: var(--fg); }
        .roster-time { color: var(--fg-mute); font-size: 11px; letter-spacing: 0.04em; text-align: right; }
        .roster-overflow {
            padding: 10px 14px; font-size: 12px; color: var(--accent);
            letter-spacing: 0.06em; border-top: 1px solid var(--hairline);
        }

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
                        @if ($showPasswordPrompt)
                            <div style="border: 1px solid var(--hairline); padding: 12px; margin-top: 8px;">
                                <div style="font-size: 11px; letter-spacing: 0.08em; text-transform: uppercase; color: var(--fg-mute); margin-bottom: 8px;">
                                    save your profile?
                                </div>
                                <input
                                    class="manifest-input"
                                    type="password"
                                    placeholder="choose a password"
                                    wire:model="newPassword"
                                >
                                @error('newPassword') <div class="manifest-error">{{ $message }}</div> @enderror
                                <div style="display: flex; gap: 8px; margin-top: 8px;">
                                    <button class="btn btn-primary" wire:click="createAccount" style="flex: 1; justify-content: center; font-size: 11px;">
                                        CREATE ACCOUNT
                                    </button>
                                    <button class="btn btn-ghost" wire:click="skipAccountCreation" style="font-size: 11px;">
                                        skip
                                    </button>
                                </div>
                            </div>
                        @endif
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
                    <a href="{{ route('events.ics', $meetup->slug) }}" class="btn btn-ghost" style="width: 100%; justify-content: center; font-size: 12px;">+ add to calendar</a>
                </div>
            </div>
        </div>

        {{-- Body --}}
        <div class="detail-body">
            <main>
                @if ($meetup->what_to_expect)
                    <h2><span class="pr">›</span>what to expect</h2>
                    <div class="prose">{!! $meetup->what_to_expect !!}</div>
                @endif

                @if ($meetup->scheduleItems->isNotEmpty())
                    <h2><span class="pr">›</span>schedule</h2>
                    <div class="schedule">
                        @foreach ($meetup->scheduleItems as $item)
                            <div class="schedule-row">
                                <div class="schedule-t">{{ $item->time }}</div>
                                <div class="schedule-what">
                                    <b>{{ $item->title }}</b>
                                    @if ($item->note)
                                        <span>{{ $item->note }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($meetup->images->isNotEmpty())
                    <h2><span class="pr">›</span>photos</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 12px;">
                        @foreach ($meetup->images as $image)
                            <img
                                src="{{ \Illuminate\Support\Facades\Storage::url($image->path) }}"
                                alt="{{ $image->alt ?? $meetup->title }}"
                                style="width: 100%; aspect-ratio: 4/3; object-fit: cover; border: 2px solid var(--fg);"
                            >
                        @endforeach
                    </div>
                @endif

                <h2><span class="pr">›</span>who's going</h2>
                @php
                    $rsvps = $meetup->rsvps;
                    $shown = $rsvps->take(20);
                    $overflow = $rsvps->count() - 20;
                @endphp
                @if ($rsvps->isEmpty())
                    <p style="color: var(--fg-mute); font-size: 14px;">No RSVPs yet. Be the first.</p>
                @else
                    <div class="roster">
                        <div class="roster-header">
                            <span>#</span>
                            <span>id</span>
                            <span>name</span>
                            <span style="text-align:right;">joined</span>
                        </div>
                        @foreach ($shown as $i => $rsvp)
                            @php
                                $initials = strtoupper(substr($rsvp->name, 0, 1))
                                    . strtoupper(substr(strstr($rsvp->name, ' '), 1, 1));
                            @endphp
                            <div class="roster-row">
                                <span class="roster-idx">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                <span><span class="roster-badge">{{ $initials }}</span></span>
                                <span class="roster-name">
                                    @if ($rsvp->user?->username)
                                        <a href="/members/{{ $rsvp->user->username }}" style="color: var(--accent); text-decoration: none;">{{ $rsvp->name }}</a>
                                    @else
                                        {{ $rsvp->name }}
                                    @endif
                                </span>
                                <span class="roster-time">{{ $rsvp->created_at->diffForHumans() }}</span>
                            </div>
                        @endforeach
                        @if ($overflow > 0)
                            <div class="roster-overflow">+ {{ $overflow }} more going</div>
                        @endif
                    </div>
                @endif
            </main>

            <aside></aside>
        </div>
    </div>
</div>
