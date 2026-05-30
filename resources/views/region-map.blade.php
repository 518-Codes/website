@extends('layouts.app')

@section('title', '/map — region sandbox tuning · 518.codes dev')

@push('styles')
<style>
    .map-root {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 0;
        min-height: calc(100vh - 60px);
        border-top: 1px solid var(--hairline);
    }

    /* Stage (left / main) */
    .map-stage {
        position: relative;
        background: var(--term-bg);
        border-right: 2px solid var(--rule-2);
    }

    [data-region-sandbox] {
        position: relative;
        width: 100%;
        height: 100%;
        min-height: 70vh;
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
        min-height: 70vh;
    }

    .region-labels {
        position: absolute;
        inset: 0;
        pointer-events: none;
        overflow: hidden;
    }

    /* Sidebar / control panel */
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
        color: var(--bg);
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

    input[type="range"]:hover {
        background: var(--rule-2);
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
        background: var(--bg);
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
        background: transparent;
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
        min-height: 0;
        max-height: 220px;
        overflow-y: auto;
        display: none;
    }

    .ctl-copy-out:not(:empty) {
        display: block;
    }

    .map-dev-note {
        padding: 10px 16px 14px;
        font-size: 10px;
        color: var(--fg-mute);
        letter-spacing: 0.04em;
        line-height: 1.6;
        border-top: 1px solid var(--rule);
        margin-top: auto;
    }

    .map-dev-note strong {
        color: var(--amber);
    }

    @media (max-width: 900px) {
        .map-root {
            grid-template-columns: 1fr;
            grid-template-rows: 60vh auto;
        }

        .map-stage {
            border-right: none;
            border-bottom: 2px solid var(--rule-2);
        }

        [data-region-sandbox],
        .region-canvas {
            min-height: 60vh;
        }

        .map-panel {
            overflow-y: visible;
        }
    }
</style>
@endpush

@section('content')
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
        </div>

        <div class="panel-section">
            <div class="panel-section-title">layers</div>

            <label class="ctl-check-row">
                <input type="checkbox" id="ctl-layer-roads">
                <span class="ctl-check-label">roads</span>
            </label>

            <label class="ctl-check-row">
                <input type="checkbox" id="ctl-layer-water">
                <span class="ctl-check-label">water</span>
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

        <div class="map-dev-note">
            <strong>dev page</strong> — tune the sandbox parameters and copy the result.<br>
            Not linked from the public site.
        </div>
    </aside>

</div>
@endsection
