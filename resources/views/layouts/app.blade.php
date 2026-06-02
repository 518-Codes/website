<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $title ?? '518.codes — meetups for people who write code in the 518')</title>
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

        /* Terminal section */
        .terminal-section { }
        .term-shell {
            max-width: 1200px; margin: 0 auto;
            background: var(--surface); font-family: var(--font-mono);
        }
        .term-titlebar {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 16px; background: var(--fg); color: var(--bg);
            font-size: 12px; letter-spacing: 0.08em; user-select: none;
        }
        .term-dots { display: flex; gap: 6px; }
        .term-dot { width: 12px; height: 12px; border-radius: 50%; }
        .term-dot-red    { background: #FF5F57; }
        .term-dot-yellow { background: #FEBC2E; }
        .term-dot-green  { background: var(--accent); }
        .term-title { flex: 1; text-align: center; font-weight: 700; }
        .term-tag { font-size: 10px; letter-spacing: 0.14em; text-transform: uppercase; opacity: 0.6; }

        .term-body {
            padding: 20px 24px 0;
            min-height: 520px; max-height: 800px; overflow-y: auto;
            display: flex; flex-direction: column; gap: 2px;
            scroll-behavior: smooth;
        }
        .term-body::-webkit-scrollbar { width: 6px; }
        .term-body::-webkit-scrollbar-track { background: transparent; }
        .term-body::-webkit-scrollbar-thumb { background: var(--rule-2); }

        .term-line {
            font-size: 13px; line-height: 1.55; white-space: pre;
            color: var(--fg-dim); min-height: 1lh;
        }
        .term-input  { color: var(--fg); }
        .term-system { color: var(--fg-mute); font-style: italic; }
        .term-error  { color: var(--amber); }
        .term-success{ color: var(--accent); font-weight: 700; }
        .term-accent { color: var(--accent); font-weight: 800; font-size: 15px; }
        .term-dim    { color: var(--fg-mute); }
        .term-prompt { color: var(--phosphor-dim); }

        .term-input-row {
            display: flex; align-items: center; gap: 8px;
            padding: 16px 0 20px; position: sticky; bottom: 0;
            background: var(--surface);
        }
        .term-ps1 { color: var(--accent); font-weight: 700; font-size: 13px; min-width: 2ch; }
        .term-input-field {
            flex: 1; background: transparent; border: none; outline: none;
            color: var(--fg); font-family: var(--font-mono); font-size: 13px;
            caret-color: var(--accent);
        }
        .term-cursor {
            display: inline-block; width: 8px; height: 14px;
            background: var(--accent); vertical-align: middle;
            animation: blink 1s step-end infinite;
        }
        .term-input-field:focus ~ .term-cursor { opacity: 1; }
        .term-loading .term-input-field { opacity: 0.5; }
        @keyframes blink { 50% { opacity: 0; } }

        @media (max-width: 640px) {
            .term-body { min-height: 120px; max-height: 280px; padding: 14px 16px 0; }
            .term-input-row { padding: 12px 0 16px; }
            .term-line { white-space: pre-wrap; }
        }

        /* Footer */
        .footer { border-top: 2px solid var(--fg); padding: 40px 32px 32px; }
        .footer-rule { color: var(--accent); overflow: hidden; white-space: nowrap; font-family: var(--font-mono); font-size: 13px; line-height: 1; user-select: none; margin-bottom: 32px; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 32px; }
        .footer-desc { color: var(--fg-dim); font-size: 13px; margin-top: 12px; max-width: 32ch; line-height: 1.5; }
        .footer-col-title { font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; color: var(--fg-mute); font-weight: 600; margin-bottom: 10px; }
        .footer-link { display: block; margin-bottom: 6px; color: var(--fg-dim); text-decoration: none; font-size: 14px; }
        .footer-link:hover { color: var(--accent); background: transparent; }
        .footer-bottom { display: flex; justify-content: space-between; align-items: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--hairline); color: var(--fg-mute); font-size: 12px; }
        .footer-status { color: var(--accent); }

        @media (max-width: 900px) {
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 24px; }
            .nav { padding: 16px 20px; }
        }

        @media (max-width: 640px) {
            .nav-links { display: none; }
            .footer-grid { grid-template-columns: 1fr; }
        }

        @yield('styles')
    </style>
    @stack('styles')
    @livewireStyles
</head>
<body>

<nav class="nav">
    <a href="/" class="nav-brand">
        <span class="dollar">$</span>518<span class="dot">.</span>codes
    </a>
    <ul class="nav-links">
        <li><a href="/events" @class(['active' => request()->is('events*')])>events</a></li>
        <li><a href="/#about">about</a></li>
        <li><a href="{{ route('host') }}" @class(['active' => request()->is('host')])>host</a></li>
    </ul>
    <a href="{{ config('community.discord_url') }}" target="_blank" rel="noopener" class="btn btn-primary" style="padding: 8px 16px; font-size: 12px; letter-spacing: 0.12em;">
        JOIN DISCORD →
    </a>
</nav>

{{ $slot ?? '' }}
@yield('content')

<section class="terminal-section" aria-label="Interactive terminal" style="margin-top: 64px;">
    @livewire('terminal')
</section>

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
            @foreach(['events' => '/events', 'host an event' => route('host'), 'code of conduct' => route('code-of-conduct'), 'about' => '/#about'] as $label => $href)
                <a href="{{ $href }}" class="footer-link">› {{ $label }}</a>
            @endforeach
            <a href="{{ config('community.discord_url') }}" target="_blank" rel="noopener" class="footer-link">› discord</a>
        </div>
        <div>
            <div class="footer-col-title">region</div>
            <div style="color: var(--fg-dim); font-size: 13px; line-height: 1.8;">
                @foreach(config('cities') as $city){{ $city }}@if(!$loop->last)<br>@endif @endforeach
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div>© 518.codes · est. 2026 · made in upstate ny</div>
        <div><span class="footer-status">● online</span></div>
    </div>
</footer>

@livewireScripts
</body>
</html>
