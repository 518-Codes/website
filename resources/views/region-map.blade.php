<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>518.codes — region map</title>
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
            --cyan: #5EE2FC;
            --rule: #1F2A1F;
            --rule-2: #2A3A2A;
            --surface: var(--term-bg-2);
            --surface-2: var(--term-bg-3);
            --fg: var(--term-fg);
            --fg-dim: var(--term-fg-dim);
            --fg-mute: var(--term-fg-mute);
            --accent: var(--phosphor);
            --hairline: var(--rule);
            --font-mono: 'JetBrains Mono', ui-monospace, monospace;
            --font-display: 'VT323', 'JetBrains Mono', monospace;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0;
            background: var(--term-bg);
            color: var(--fg);
            font-family: var(--font-mono);
            font-size: 15px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: var(--surface); }
        ::-webkit-scrollbar-thumb { background: var(--rule-2); border: 2px solid var(--surface); }
        ::-webkit-scrollbar-thumb:hover { background: var(--accent); }

        /* Layout: full-viewport stage + compact control sidebar */
        .map-root {
            display: grid;
            grid-template-columns: 1fr 320px;
            height: 100vh;
            width: 100vw;
        }

        /* Stage */
        .map-stage {
            position: relative;
            background: var(--term-bg);
            border-right: 2px solid var(--rule-2);
            overflow: hidden;
        }

        [data-region-sandbox] {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        [data-region-sandbox][data-unsupported]::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--fallback-img, none) center / cover no-repeat;
            opacity: 0.6;
        }

        [data-region-sandbox][data-unsupported]::after {
            content: 'WebGL unsupported — showing fallback';
            position: absolute;
            bottom: 12px;
            left: 12px;
            font-size: 11px;
            color: var(--amber);
            letter-spacing: 0.06em;
        }

        .region-canvas {
            display: block;
            width: 100%;
            height: 100%;
        }

        /* Map pins / labels */
        .region-labels { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
        .region-label {
            position: absolute; transform: translate(-50%, -120%);
            color: var(--phosphor); font-family: var(--font-mono); font-size: 12px;
            font-weight: 700; letter-spacing: 0.06em; text-shadow: 0 0 6px rgba(94,252,141,0.6);
            white-space: nowrap;
        }
        .region-label::after { content: '◆'; display: block; text-align: center; font-size: 9px; opacity: 0.8; }

        /* Brand overlay */
        .brand-overlay {
            position: fixed; left: 16px; bottom: 14px; z-index: 50;
            font-family: var(--font-mono); font-weight: 800; font-size: 16px;
            letter-spacing: -0.02em; color: var(--fg); pointer-events: none;
            text-shadow: 0 0 8px rgba(0,0,0,0.7);
        }
        .brand-overlay .b-accent { color: var(--accent); }

        /* Control panel */
        .map-panel {
            background: var(--surface);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .map-panel-header {
            padding: 14px 16px;
            background: var(--fg);
            color: var(--term-bg);
            font-size: 11px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .map-panel-header .map-dots {
            display: flex;
            gap: 5px;
        }

        .map-panel-header .map-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .panel-section {
            padding: 14px 16px;
            border-bottom: 1px solid var(--rule);
        }

        .panel-section-title {
            font-size: 10px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--fg-mute);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .ctl-row {
            display: flex;
            flex-direction: column;
            gap: 3px;
            margin-bottom: 10px;
        }

        .ctl-row:last-child {
            margin-bottom: 0;
        }

        .ctl-label-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .ctl-label {
            font-size: 11px;
            color: var(--fg-dim);
            letter-spacing: 0.04em;
        }

        .ctl-val {
            font-size: 11px;
            color: var(--phosphor);
            font-weight: 700;
            min-width: 4ch;
            text-align: right;
            font-family: var(--font-mono);
        }

        input[type="range"] {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 4px;
            background: var(--rule-2);
            outline: none;
            cursor: pointer;
        }

        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 12px;
            height: 12px;
            background: var(--phosphor);
            cursor: pointer;
        }

        input[type="range"]::-moz-range-thumb {
            width: 12px;
            height: 12px;
            background: var(--phosphor);
            cursor: pointer;
            border: none;
        }

        input[type="range"]:focus::-webkit-slider-thumb {
            box-shadow: 0 0 0 2px var(--phosphor);
        }

        /* Checkboxes */
        .ctl-check-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            cursor: pointer;
        }

        .ctl-check-row:last-child {
            margin-bottom: 0;
        }

        .ctl-check-row input[type="checkbox"] {
            -webkit-appearance: none;
            appearance: none;
            width: 14px;
            height: 14px;
            border: 1.5px solid var(--fg-mute);
            background: transparent;
            cursor: pointer;
            flex-shrink: 0;
            position: relative;
        }

        .ctl-check-row input[type="checkbox"]:checked {
            background: var(--phosphor);
            border-color: var(--phosphor);
        }

        .ctl-check-row input[type="checkbox"]:checked::after {
            content: '';
            position: absolute;
            inset: 2px;
            background: var(--term-bg);
            clip-path: polygon(14% 44%, 0 65%, 50% 100%, 100% 16%, 80% 0%, 43% 62%);
        }

        .ctl-check-label {
            font-size: 11px;
            color: var(--fg-dim);
            letter-spacing: 0.04em;
            user-select: none;
        }

        /* Copy button & output */
        .ctl-copy-btn {
            display: block;
            width: 100%;
            background: transparent;
            border: 1.5px solid var(--fg-mute);
            color: var(--fg-dim);
            font-family: var(--font-mono);
            font-size: 11px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 7px 10px;
            cursor: pointer;
            text-align: left;
            margin-bottom: 10px;
            transition: border-color 100ms, color 100ms;
        }

        .ctl-copy-btn:hover {
            border-color: var(--phosphor);
            color: var(--phosphor);
        }

        .ctl-copy-out {
            font-size: 10px;
            color: var(--phosphor-dim);
            background: var(--term-bg);
            border: 1px solid var(--rule);
            padding: 8px;
            margin: 0;
            white-space: pre-wrap;
            word-break: break-all;
            line-height: 1.5;
            max-height: 220px;
            overflow-y: auto;
            display: none;
        }

        .ctl-copy-out:not(:empty) {
            display: block;
        }

        @media (max-width: 900px) {
            .map-root {
                grid-template-columns: 1fr;
                grid-template-rows: 60vh auto;
                height: auto;
                min-height: 100vh;
            }

            .map-stage {
                border-right: none;
                border-bottom: 2px solid var(--rule-2);
            }
        }
    </style>
</head>
<body>

<div class="map-root" data-region-map>

    {{-- Stage --}}
    <div class="map-stage">
        <div data-region-sandbox
             data-assets="{{ asset('region-sandbox') }}"
             data-fallback="{{ asset('region-sandbox/fallback.jpg') }}">
            <canvas class="region-canvas"></canvas>
            <div class="region-labels" aria-hidden="true"></div>
        </div>
    </div>

    {{-- Control panel --}}
    <aside class="map-panel">
        <div class="map-panel-header">
            <div class="map-dots">
                <div class="map-dot" style="background:#FF5F57"></div>
                <div class="map-dot" style="background:#FEBC2E"></div>
                <div class="map-dot" style="background:var(--accent)"></div>
            </div>
            <span>region-sandbox / tune</span>
        </div>

        {{-- Sliders --}}
        <div class="panel-section">
            <div class="panel-section-title">rendering</div>

            <div class="ctl-row">
                <div class="ctl-label-row">
                    <label class="ctl-label" for="ctl-relief">relief</label>
                    <span class="ctl-val" id="val-relief">—</span>
                </div>
                <input type="range" id="ctl-relief" min="0" max="1" step="0.01">
            </div>

            <div class="ctl-row">
                <div class="ctl-label-row">
                    <label class="ctl-label" for="ctl-glow">glow</label>
                    <span class="ctl-val" id="val-glow">—</span>
                </div>
                <input type="range" id="ctl-glow" min="0" max="1" step="0.05">
            </div>

            <div class="ctl-row">
                <div class="ctl-label-row">
                    <label class="ctl-label" for="ctl-cell">resolution (cell px, lower = denser)</label>
                    <span class="ctl-val" id="val-cell">—</span>
                </div>
                <input type="range" id="ctl-cell" min="3" max="16" step="1">
            </div>

            <div class="ctl-row">
                <div class="ctl-label-row">
                    <label class="ctl-label" for="ctl-light">light</label>
                    <span class="ctl-val" id="val-light">—</span>
                </div>
                <input type="range" id="ctl-light" min="0" max="4" step="0.1">
            </div>
        </div>

        <div class="panel-section">
            <div class="panel-section-title">camera</div>

            <div class="ctl-row">
                <div class="ctl-label-row">
                    <label class="ctl-label" for="ctl-radius">zoom (radius)</label>
                    <span class="ctl-val" id="val-radius">—</span>
                </div>
                <input type="range" id="ctl-radius" min="1" max="4" step="0.1">
            </div>

            <div class="ctl-row">
                <div class="ctl-label-row">
                    <label class="ctl-label" for="ctl-tilt">tilt (deg)</label>
                    <span class="ctl-val" id="val-tilt">—</span>
                </div>
                <input type="range" id="ctl-tilt" min="5" max="80" step="1">
            </div>

            <div class="ctl-row">
                <div class="ctl-label-row">
                    <label class="ctl-label" for="ctl-orbitSpeed">orbit speed</label>
                    <span class="ctl-val" id="val-orbitSpeed">—</span>
                </div>
                <input type="range" id="ctl-orbitSpeed" min="0" max="0.01" step="0.0005">
            </div>
        </div>

        <div class="panel-section">
            <div class="panel-section-title">toggles</div>

            <label class="ctl-check-row">
                <input type="checkbox" id="ctl-mono">
                <span class="ctl-check-label">monochrome</span>
            </label>

            <label class="ctl-check-row">
                <input type="checkbox" id="ctl-elevation">
                <span class="ctl-check-label">elevation banding</span>
            </label>

            <div class="ctl-row" style="margin-top:10px">
                <div class="ctl-label-row">
                    <label class="ctl-label" for="ctl-bandCount">elevation bands</label>
                    <span class="ctl-val" id="val-bandCount">—</span>
                </div>
                <input type="range" id="ctl-bandCount" min="2" max="24" step="1">
            </div>

            <div class="ctl-row">
                <div class="ctl-label-row">
                    <label class="ctl-label" for="ctl-bandCurve">band curve (lower = more low detail)</label>
                    <span class="ctl-val" id="val-bandCurve">—</span>
                </div>
                <input type="range" id="ctl-bandCurve" min="0.3" max="1.5" step="0.05">
            </div>
        </div>

        <div class="panel-section">
            <div class="panel-section-title">layers</div>

            <label class="ctl-check-row">
                <input type="checkbox" id="ctl-layer-road-major">
                <span class="ctl-check-label">major highways</span>
            </label>

            <label class="ctl-check-row">
                <input type="checkbox" id="ctl-layer-road-sub">
                <span class="ctl-check-label">sub highways</span>
            </label>

            <label class="ctl-check-row">
                <input type="checkbox" id="ctl-layer-water-river">
                <span class="ctl-check-label">rivers / canals</span>
            </label>

            <label class="ctl-check-row">
                <input type="checkbox" id="ctl-layer-water-stream">
                <span class="ctl-check-label">streams</span>
            </label>

            <label class="ctl-check-row">
                <input type="checkbox" id="ctl-layer-water-area">
                <span class="ctl-check-label">lakes / ponds</span>
            </label>

            <label class="ctl-check-row">
                <input type="checkbox" id="ctl-layer-terrain">
                <span class="ctl-check-label">terrain</span>
            </label>
        </div>

        <div class="panel-section">
            <div class="panel-section-title">export</div>
            <button class="ctl-copy-btn" id="ctl-copy">› copy values</button>
            <pre class="ctl-copy-out" id="ctl-copy-out"></pre>
        </div>
    </aside>

</div>

<div class="brand-overlay"><span class="b-accent">$</span>518<span class="b-accent">.</span>codes</div>

</body>
</html>
