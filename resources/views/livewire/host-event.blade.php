<div>
    <style>
        .host-page { max-width: 900px; margin: 0 auto; padding: 56px 32px 0; }
        .host-page-head { margin-bottom: 48px; border-bottom: 2px solid var(--fg); padding-bottom: 24px; }
        .host-page-head .kicker { margin-bottom: 12px; }
        .host-page-head h1 { font-size: 56px; letter-spacing: -0.02em; margin: 0 0 14px; line-height: 1; font-weight: 800; }
        .host-page-head p { color: var(--fg-dim); font-size: 16px; line-height: 1.6; max-width: 52ch; margin: 0; }

        .host-form { display: flex; flex-direction: column; gap: 28px; }

        .field { display: flex; flex-direction: column; gap: 8px; }
        .field-label {
            font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase;
            color: var(--fg-mute); font-weight: 700;
        }
        .field-label .req { color: var(--accent); margin-left: 2px; }
        .field-input {
            padding: 12px 16px; border: 2px solid var(--fg); background: var(--bg);
            color: var(--fg); font-family: var(--font-mono); font-size: 14px;
            outline: none; width: 100%; transition: border-color 100ms;
        }
        .field-input:focus { border-color: var(--accent); }
        .field-input::placeholder { color: var(--fg-mute); }
        textarea.field-input { resize: vertical; min-height: 140px; }
        .field-hint { font-size: 12px; color: var(--fg-mute); }
        .field-error { font-size: 12px; color: var(--amber); }

        .host-rules {
            border: 2px solid var(--fg); background: var(--surface);
            padding: 20px 24px; display: flex; flex-direction: column; gap: 8px;
        }
        .host-rules .rule { font-size: 13px; color: var(--fg-dim); padding-left: 16px; position: relative; }
        .host-rules .rule::before { content: '›'; position: absolute; left: 0; color: var(--accent); }

        .host-success {
            border: 4px solid var(--accent); box-shadow: 8px 8px 0 0 var(--accent);
            padding: 40px; text-align: center;
        }
        .host-success h2 { font-size: 36px; margin: 16px 0 12px; font-weight: 800; }
        .host-success p { color: var(--fg-dim); font-size: 15px; max-width: 44ch; margin: 0 auto 24px; line-height: 1.6; }

        @media (max-width: 640px) {
            .host-page { padding: 40px 20px 0; }
            .host-page-head h1 { font-size: 40px; }
        }
    </style>

    <div class="host-page">

        @if ($submitted)
            <div class="host-success">
                <div class="kicker">proposal received</div>
                <h2>You're on the list.</h2>
                <p>
                    We'll review your proposal and reach out to {{ $contact_email }} within a few days
                    to sort out the details. See you soon.
                </p>
                <a href="{{ route('events.index') }}" class="btn btn-primary">← BACK TO EVENTS</a>
            </div>
        @else
            <div class="host-page-head">
                <div class="kicker">// host.submit</div>
                <h1>Host an event.</h1>
                <p>
                    Got a topic, a venue, or just a Tuesday? Submit a proposal below.
                    We'll handle the rest — copy, RSVPs, and getting the word out.
                </p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 280px; gap: 48px; align-items: start;">
                <form class="host-form" wire:submit="submit">

                    <div class="field">
                        <label class="field-label" for="title">
                            Event title <span class="req">*</span>
                        </label>
                        <input
                            id="title"
                            class="field-input"
                            type="text"
                            wire:model="title"
                            placeholder="e.g. Albany Postgres Night"
                            autocomplete="off"
                        >
                        @error('title') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label class="field-label" for="location">
                            Venue / location <span class="req">*</span>
                        </label>
                        <input
                            id="location"
                            class="field-input"
                            type="text"
                            wire:model="location"
                            placeholder="e.g. Troy Public Library, 100 2nd St, Troy, NY"
                            autocomplete="off"
                        >
                        @error('location') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label class="field-label" for="proposed_date">
                            Proposed date &amp; time <span class="req">*</span>
                        </label>
                        <input
                            id="proposed_date"
                            class="field-input"
                            type="datetime-local"
                            wire:model="proposed_date"
                        >
                        <div class="field-hint">Flexible? Leave a note in the description.</div>
                        @error('proposed_date') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label class="field-label" for="description">
                            What's it about? <span class="req">*</span>
                        </label>
                        <textarea
                            id="description"
                            class="field-input"
                            wire:model="description"
                            placeholder="A short paragraph about the event — topic, format, what people can expect. The rougher the better."
                        ></textarea>
                        @error('description') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label class="field-label" for="contact_email">
                            Your email <span class="req">*</span>
                        </label>
                        <input
                            id="contact_email"
                            class="field-input"
                            type="email"
                            wire:model="contact_email"
                            placeholder="you@example.com"
                            autocomplete="email"
                        >
                        <div class="field-hint">Only used to follow up about your proposal. Never shared.</div>
                        @error('contact_email') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>SUBMIT PROPOSAL →</span>
                            <span wire:loading>SUBMITTING...</span>
                        </button>
                    </div>

                </form>

                <div style="position: sticky; top: 96px; display: flex; flex-direction: column; gap: 16px;">
                    <div class="kicker kicker-mute">// how it works</div>
                    <div class="host-rules">
                        <div class="rule">Submit your proposal — a topic, a venue, a rough date.</div>
                        <div class="rule">We review it and reach out within a few days.</div>
                        <div class="rule">Once approved, we list it and open RSVPs.</div>
                        <div class="rule">You show up and run it. We handle the rest.</div>
                    </div>
                    <div class="kicker kicker-mute" style="margin-top: 8px;">// ground rules</div>
                    <div class="host-rules">
                        <div class="rule">Free to attend. Always.</div>
                        <div class="rule">No sales pitches or recruiting disguised as talks.</div>
                        <div class="rule">Any skill level welcome — senior to first-timer.</div>
                        <div class="rule"><a href="{{ route('code-of-conduct') }}">Code of conduct</a> applies.</div>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
