<div class="term-shell" x-data="terminalShell()" @keydown.window="focusInput">

    <div class="term-titlebar" @click="toggle" style="cursor: pointer;" title="click to minimize / restore">
        <div class="term-dots">
            <span class="term-dot term-dot-red"></span>
            <span class="term-dot term-dot-yellow" title="minimize"></span>
            <span class="term-dot term-dot-green"></span>
        </div>
        <div class="term-title">518.codes — bash</div>
        <div class="term-tag" x-text="minimized ? 'minimized' : 'interactive'"></div>
    </div>

    <div class="term-body" x-ref="body" x-show="!minimized" x-transition:enter="term-expand-enter" x-transition:enter-end="term-expand-enter-end" x-transition:leave="term-expand-leave" x-transition:leave-end="term-expand-leave-end">
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
        </div>
    </div>

</div>

<style>
    .term-expand-enter      { overflow: hidden; max-height: 0; opacity: 0; }
    .term-expand-enter-end  { overflow: hidden; max-height: 800px; opacity: 1; transition: max-height 300ms ease, opacity 200ms ease; }
    .term-expand-leave      { overflow: hidden; max-height: 800px; opacity: 1; }
    .term-expand-leave-end  { overflow: hidden; max-height: 0; opacity: 0; transition: max-height 250ms ease, opacity 150ms ease; }
</style>

<script>
function terminalShell() {
    return {
        minimized: false,
        toggle() {
            this.minimized = !this.minimized;
            if (!this.minimized) {
                this.$nextTick(() => {
                    this.$refs.body.scrollTop = this.$refs.body.scrollHeight;
                    this.$refs.input?.focus();
                });
            }
        },
        focusInput(e) {
            if (this.minimized) return;
            const tag = e.target.tagName;
            if (tag === 'INPUT' || tag === 'TEXTAREA') return;
            if (e.metaKey || e.ctrlKey || e.altKey) return;
            this.$refs.input?.focus();
        },
        init() {
            this.$watch('$wire.history', () => {
                if (this.minimized) return;
                this.$nextTick(() => {
                    this.$refs.body.scrollTop = this.$refs.body.scrollHeight;
                });
            });
            this.$refs.input?.focus();
        }
    }
}
</script>
