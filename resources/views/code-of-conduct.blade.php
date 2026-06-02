@extends('layouts.app')

@section('title', '518.codes — code of conduct')

@section('styles')
        .coc-page { max-width: 760px; margin: 0 auto; padding: 56px 32px 0; }
        .coc-head { margin-bottom: 40px; border-bottom: 2px solid var(--fg); padding-bottom: 24px; }
        .coc-head .kicker { margin-bottom: 12px; }
        .coc-head h1 { font-size: 56px; letter-spacing: -0.02em; margin: 0 0 14px; line-height: 1; font-weight: 800; }
        .coc-head p { color: var(--fg-dim); font-size: 16px; line-height: 1.6; max-width: 56ch; margin: 0; }

        .coc-rules { display: flex; flex-direction: column; gap: 4px; counter-reset: rule; }
        .coc-rule {
            border: 2px solid var(--fg); background: var(--surface);
            padding: 18px 20px 18px 56px; position: relative;
        }
        .coc-rule::before {
            counter-increment: rule; content: counter(rule, decimal-leading-zero);
            position: absolute; left: 18px; top: 18px;
            font-family: var(--font-display); font-size: 22px; color: var(--accent); line-height: 1;
        }
        .coc-rule b { color: var(--fg); font-weight: 700; }
        .coc-rule span { color: var(--fg-dim); }

        .coc-report {
            margin: 32px 0 0; border: 4px solid var(--accent); box-shadow: 8px 8px 0 0 var(--accent);
            padding: 24px 28px;
        }
        .coc-report .kicker { margin-bottom: 8px; }
        .coc-report p { color: var(--fg-dim); font-size: 15px; line-height: 1.6; margin: 0; max-width: 52ch; }

        @media (max-width: 640px) {
            .coc-page { padding: 40px 20px 0; }
            .coc-head h1 { font-size: 40px; }
        }
@endsection

@section('content')
<div class="coc-page">
    <div class="coc-head">
        <div class="kicker">// code-of-conduct</div>
        <h1>Code of Conduct</h1>
        <p>518.codes is a space for local developers to connect. A few ground rules keep it that way:</p>
    </div>

    <div class="coc-rules">
        <div class="coc-rule">
            <b>Be respectful.</b>
            <span>Treat people the way you would at an in-person meetup. No harassment, personal attacks, or discrimination. Disagree about tabs vs. spaces all you want, but keep it civil.</span>
        </div>
        <div class="coc-rule">
            <b>Keep it on-topic for the space.</b>
            <span>This is a community for local developers to connect. General tech talk, career chat, and hanging out are all welcome. Read the channel descriptions and post in the right place.</span>
        </div>
        <div class="coc-rule">
            <b>No spam or self-promotion without asking.</b>
            <span>Sharing your project or a job opening is fine — that's part of why we're here. But don't blast links, ads, or recruiting messages across channels. If you're unsure, ask an organizer.</span>
        </div>
        <div class="coc-rule">
            <b>No NSFW or illegal content.</b>
            <span>Keep everything appropriate for a public, professional-ish community.</span>
        </div>
        <div class="coc-rule">
            <b>Respect privacy.</b>
            <span>Don't share other members' personal info, and don't post photos or details from meetups without the people involved being okay with it.</span>
        </div>
        <div class="coc-rule">
            <b>Listen to the organizers.</b>
            <span>We're here to keep things running smoothly. If an organizer asks you to dial something back, please do.</span>
        </div>
    </div>

    <div class="coc-report">
        <div class="kicker">// reporting</div>
        <p>See something off? Reach out to an organizer on <a href="{{ config('community.discord_url') }}" target="_blank" rel="noopener">Discord</a> and we'll handle it.</p>
    </div>
</div>
@endsection
