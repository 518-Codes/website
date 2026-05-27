<div class="term-shell" x-data="terminalShell()" @keydown.window="focusInput">

    <div class="term-titlebar">
        <div class="term-dots">
            <span class="term-dot term-dot-red"></span>
            <span class="term-dot term-dot-yellow"></span>
            <span class="term-dot term-dot-green"></span>
        </div>
        <div class="term-title">518.codes — bash</div>
        <div class="term-tag">interactive</div>
    </div>

    <div class="term-body" x-ref="body">
        @foreach ($history as $line)
            @if ($line['type'] === 'input')
                <div class="term-line term-input">{{ $line['text'] }}</div>
            @elseif ($line['type'] === 'system')
                <div class="term-line term-system">{{ $line['text'] }}</div>
            @elseif ($line['type'] === 'error')
                <div class="term-line term-error">✗ {{ $line['text'] }}</div>
            @elseif ($line['type'] === 'success')
                <div class="term-line term-success">{{ $line['text'] }}</div>
            @elseif ($line['type'] === 'accent')
                <div class="term-line term-accent">{{ $line['text'] }}</div>
            @elseif ($line['type'] === 'dim')
                <div class="term-line term-dim">{{ $line['text'] }}</div>
            @elseif ($line['type'] === 'prompt')
                <div class="term-line term-prompt">› {{ $line['text'] }}</div>
            @else
                <div class="term-line">{{ $line['text'] }}</div>
            @endif
        @endforeach

        <div class="term-input-row" wire:loading.class="term-loading">
            <span class="term-ps1">
                @if ($rsvpState === 'rsvp-name')
                    name
                @elseif ($rsvpState === 'rsvp-email')
                    email
                @else
                    $
                @endif
            </span>
            <input
                class="term-input-field"
                type="{{ $rsvpState === 'rsvp-email' ? 'email' : 'text' }}"
                wire:model="input"
                wire:keydown.enter="submit"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
                spellcheck="false"
                placeholder="{{ $rsvpState ? '' : 'type a command…' }}"
                x-ref="input"
            >
            <span class="term-cursor" aria-hidden="true"></span>
        </div>
    </div>

</div>

<script>
function terminalShell() {
    return {
        focusInput(e) {
            const tag = e.target.tagName;
            if (tag === 'INPUT' || tag === 'TEXTAREA') return;
            if (e.metaKey || e.ctrlKey || e.altKey) return;
            this.$refs.input?.focus();
        },
        init() {
            this.$watch('$wire.history', () => {
                this.$nextTick(() => {
                    this.$refs.body.scrollTop = this.$refs.body.scrollHeight;
                });
            });
            this.$refs.input?.focus();
        }
    }
}
</script>
