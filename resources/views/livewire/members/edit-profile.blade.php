<div>
    <style>
        .edit-page { max-width: 760px; margin: 60px auto; padding: 0 32px 80px; }
        .edit-box { border: 2px solid var(--fg); box-shadow: var(--shadow-2); }
        .edit-head { background: var(--fg); color: var(--bg); padding: 8px 14px; font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; font-weight: 700; }
        .edit-body { padding: 24px; display: flex; flex-direction: column; gap: 18px; }
        .edit-section-title { font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--accent); font-weight: 700; padding-top: 8px; border-top: 1px solid var(--hairline); }
        .edit-field { display: flex; flex-direction: column; gap: 4px; }
        .edit-label { font-size: 11px; letter-spacing: 0.08em; text-transform: uppercase; color: var(--fg-mute); }
        .edit-input {
            padding: 8px 12px; border: 2px solid var(--fg); background: var(--bg);
            color: var(--fg); font-family: var(--font-mono); font-size: 13px; outline: none;
        }
        .edit-input:focus { border-color: var(--accent); }
        .edit-textarea { resize: vertical; min-height: 80px; }
        .edit-error { font-size: 11px; color: var(--amber); margin-top: 2px; }
        .skills-grid { display: flex; flex-wrap: wrap; gap: 8px; }
        .skill-toggle { cursor: pointer; }
        .skill-toggle input { display: none; }
        .skill-toggle .chip { cursor: pointer; }
        .skill-toggle input:checked + .chip { background: var(--accent); color: var(--bg); border-color: var(--accent); }
    </style>

    <div class="edit-page">
        <div class="edit-box">
            <div class="edit-head">// edit profile · {{ $member->username }}</div>
            <div class="edit-body">

                <div class="edit-section-title">basics</div>

                <div class="edit-field">
                    <label class="edit-label">headline</label>
                    <input class="edit-input" type="text" wire:model="headline" placeholder="e.g. Full-stack dev at Acme">
                    @error('headline') <div class="edit-error">{{ $message }}</div> @enderror
                </div>

                <div class="edit-field">
                    <label class="edit-label">bio</label>
                    <textarea class="edit-input edit-textarea" wire:model="bio" placeholder="A bit about yourself..."></textarea>
                    @error('bio') <div class="edit-error">{{ $message }}</div> @enderror
                </div>

                <div class="edit-field">
                    <label class="edit-label">company</label>
                    <input class="edit-input" type="text" wire:model="company" placeholder="Where do you work?">
                    @error('company') <div class="edit-error">{{ $message }}</div> @enderror
                </div>

                <div class="edit-field">
                    <label class="edit-label">avatar</label>
                    <input class="edit-input" type="file" wire:model="avatar" accept="image/*">
                    @error('avatar') <div class="edit-error">{{ $message }}</div> @enderror
                </div>

                <div class="edit-section-title">skills</div>

                <div class="skills-grid">
                    @foreach ($allSkills as $skill)
                        <label class="skill-toggle">
                            <input
                                type="checkbox"
                                value="{{ $skill->id }}"
                                wire:model="selectedSkillIds"
                            >
                            <span class="chip chip-accent">{{ $skill->name }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="edit-section-title">socials</div>

                <div class="edit-field">
                    <label class="edit-label">github url</label>
                    <input class="edit-input" type="url" wire:model="githubUrl" placeholder="https://github.com/you">
                    @error('githubUrl') <div class="edit-error">{{ $message }}</div> @enderror
                </div>

                <div class="edit-field">
                    <label class="edit-label">twitter url</label>
                    <input class="edit-input" type="url" wire:model="twitterUrl" placeholder="https://twitter.com/you">
                    @error('twitterUrl') <div class="edit-error">{{ $message }}</div> @enderror
                </div>

                <div class="edit-field">
                    <label class="edit-label">linkedin url</label>
                    <input class="edit-input" type="url" wire:model="linkedinUrl" placeholder="https://linkedin.com/in/you">
                    @error('linkedinUrl') <div class="edit-error">{{ $message }}</div> @enderror
                </div>

                <div class="edit-field">
                    <label class="edit-label">website url</label>
                    <input class="edit-input" type="url" wire:model="websiteUrl" placeholder="https://yoursite.com">
                    @error('websiteUrl') <div class="edit-error">{{ $message }}</div> @enderror
                </div>

                <button class="btn btn-primary" wire:click="save" style="width: 100%; justify-content: center;">
                    <span wire:loading.remove wire:target="save">SAVE PROFILE →</span>
                    <span wire:loading wire:target="save">SAVING...</span>
                </button>

            </div>
        </div>
    </div>
</div>
