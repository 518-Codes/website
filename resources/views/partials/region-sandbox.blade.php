{{-- Region ASCII sandbox: 3D Capital Region rendered as phosphor ASCII. --}}
<div class="section-wrap" id="region">
    <div class="section">
        <div class="section-head">
            <h2><span style="color: var(--accent);">›</span> the region</h2>
            <div class="meta-label">drag to rotate · two fingers to pan</div>
        </div>
        <div
            class="term-shell region-sandbox"
            data-region-sandbox
            data-assets="{{ asset('region-sandbox') }}"
            data-fallback="{{ asset('region-map.png') }}"
        >
            <div class="term-titlebar">
                <div class="term-dots">
                    <span class="term-dot term-dot-red"></span>
                    <span class="term-dot term-dot-yellow"></span>
                    <span class="term-dot term-dot-green"></span>
                </div>
                <div class="term-title">capital-region.map</div>
                <div class="term-tag">ascii · 3d</div>
            </div>
            <div class="region-stage">
                <canvas class="region-canvas" aria-label="3D ASCII map of the Capital Region"></canvas>
                <div class="region-labels" aria-hidden="true"></div>
                <noscript><img src="{{ asset('region-map.png') }}" alt="Map of the Capital Region" style="width:100%;display:block"></noscript>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .region-stage { position: relative; aspect-ratio: 4 / 3; background: var(--term-bg); overflow: hidden; }
    .region-canvas { display: block; width: 100%; height: 100%; touch-action: none; }
    .region-labels { position: absolute; inset: 0; pointer-events: none; }
    .region-label {
        position: absolute; transform: translate(-50%, -120%);
        color: var(--phosphor); font-family: var(--font-mono); font-size: 12px;
        font-weight: 700; letter-spacing: 0.06em; text-shadow: 0 0 6px rgba(94,252,141,0.6);
        white-space: nowrap;
    }
    .region-label::after { content: '◆'; display: block; text-align: center; font-size: 9px; opacity: 0.8; }
    .region-sandbox[data-unsupported] .region-canvas { display: none; }
    .region-sandbox[data-unsupported] .region-stage { background-image: var(--fallback-img); background-size: cover; filter: grayscale(1) brightness(0.7) sepia(1) hue-rotate(70deg) saturate(3); }
</style>
@endpush
