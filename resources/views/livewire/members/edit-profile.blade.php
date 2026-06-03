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
        .skill-combobox { position: relative; }
        .skill-selected { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; min-height: 0; }
        .skill-chip-selected {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 2px 8px; border: 1.5px solid var(--accent);
            font-size: 11px; color: var(--accent); letter-spacing: 0.04em;
        }
        .skill-chip-remove { background: none; border: none; color: var(--accent); cursor: pointer; font-size: 14px; line-height: 1; padding: 0; margin-left: 2px; }
        .skill-chip-remove:hover { color: var(--fg); }
        .skill-dropdown {
            position: absolute; top: 100%; left: 0; right: 0; z-index: 50;
            background: var(--surface); border: 2px solid var(--fg); border-top: none;
            max-height: 200px; overflow-y: auto;
        }
        .skill-option {
            padding: 8px 12px; font-size: 13px; cursor: pointer; color: var(--fg-dim);
        }
        .skill-option:hover, .skill-option.active { background: var(--accent); color: var(--bg); }
        .skill-option-create { color: var(--fg-mute); font-style: italic; }
        .skill-option-create:hover, .skill-option-create.active { background: var(--surface-2); color: var(--accent); font-style: normal; }

        .entry-card { border: 1px solid var(--hairline); padding: 16px; display: flex; flex-direction: column; gap: 12px; position: relative; }
        .entry-card-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .entry-remove { position: absolute; top: 10px; right: 10px; background: none; border: none; color: var(--fg-mute); font-size: 18px; cursor: pointer; line-height: 1; padding: 2px 6px; font-family: var(--font-mono); }
        .entry-remove:hover { color: var(--amber); }
        .add-entry-btn { background: none; border: 1px dashed var(--fg-mute); color: var(--fg-mute); font-family: var(--font-mono); font-size: 12px; letter-spacing: 0.08em; padding: 10px; cursor: pointer; width: 100%; }
        .add-entry-btn:hover { border-color: var(--accent); color: var(--accent); }
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

                {{-- Skills --}}
                <div class="edit-section-title">skills</div>

                <div
                    class="skill-combobox"
                    x-data="{
                        query: '',
                        open: false,
                        cursor: -1,
                        get allSkills() { return $wire.skillOptions; },
                        get selectedIds() { return $wire.selectedSkillIds; },
                        get selectedSkills() {
                            return this.allSkills.filter(s => this.selectedIds.includes(s.id));
                        },
                        get filtered() {
                            if (this.query.trim() === '') return this.allSkills.filter(s => !this.selectedIds.includes(s.id)).slice(0, 12);
                            return this.allSkills.filter(s =>
                                s.name.toLowerCase().includes(this.query.toLowerCase()) &&
                                !this.selectedIds.includes(s.id)
                            );
                        },
                        get showCreate() {
                            if (this.query.trim() === '') return false;
                            return !this.allSkills.some(s => s.name.toLowerCase() === this.query.trim().toLowerCase());
                        },
                        get totalOptions() {
                            return this.filtered.length + (this.showCreate ? 1 : 0);
                        },
                        select(skill) {
                            if (!this.selectedIds.includes(skill.id)) {
                                $wire.selectedSkillIds = [...this.selectedIds, skill.id];
                            }
                            this.query = '';
                            this.cursor = -1;
                            this.open = false;
                            $refs.skillInput.focus();
                        },
                        createAndSelect() {
                            $wire.newSkillName = this.query;
                            $wire.addSkill();
                            this.query = '';
                            this.cursor = -1;
                            this.open = false;
                            $refs.skillInput.focus();
                        },
                        remove(id) {
                            $wire.selectedSkillIds = this.selectedIds.filter(i => i !== id);
                        },
                        onKeydown(e) {
                            if (!this.open && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) { this.open = true; return; }
                            if (e.key === 'ArrowDown') { e.preventDefault(); this.cursor = (this.cursor + 1) % this.totalOptions; }
                            else if (e.key === 'ArrowUp') { e.preventDefault(); this.cursor = (this.cursor - 1 + this.totalOptions) % this.totalOptions; }
                            else if (e.key === 'Enter') {
                                e.preventDefault();
                                if (this.cursor >= 0 && this.cursor < this.filtered.length) this.select(this.filtered[this.cursor]);
                                else if (this.cursor === this.filtered.length && this.showCreate) this.createAndSelect();
                                else if (this.showCreate) this.createAndSelect();
                                else if (this.filtered.length > 0) this.select(this.filtered[0]);
                            }
                            else if (e.key === 'Escape') { this.open = false; this.cursor = -1; }
                        }
                    }"
                    @click.outside="open = false; cursor = -1"
                >
                    <div class="skill-selected" x-show="selectedSkills.length > 0">
                        <template x-for="skill in selectedSkills" :key="skill.id">
                            <span class="skill-chip-selected">
                                <span x-text="skill.name"></span>
                                <button type="button" class="skill-chip-remove" @click="remove(skill.id)">×</button>
                            </span>
                        </template>
                    </div>
                    <input
                        x-ref="skillInput"
                        class="edit-input"
                        type="text"
                        x-model="query"
                        @focus="open = true"
                        @input="open = true; cursor = -1"
                        @keydown="onKeydown($event)"
                        placeholder="search or add skills..."
                        autocomplete="off"
                    >
                    <div class="skill-dropdown" x-show="open && (filtered.length > 0 || showCreate)" x-cloak>
                        <template x-for="(skill, i) in filtered" :key="skill.id">
                            <div
                                class="skill-option"
                                :class="{ active: cursor === i }"
                                @mousedown.prevent="select(skill)"
                                x-text="skill.name"
                            ></div>
                        </template>
                        <div
                            x-show="showCreate"
                            class="skill-option skill-option-create"
                            :class="{ active: cursor === filtered.length }"
                            @mousedown.prevent="createAndSelect()"
                        >
                            + create "<span x-text="query.trim()"></span>"
                        </div>
                    </div>
                </div>

                {{-- Experience --}}
                <div class="edit-section-title">experience</div>

                @foreach ($experiences as $i => $exp)
                    <div class="entry-card">
                        <button class="entry-remove" wire:click="removeExperience({{ $i }})" title="remove">×</button>
                        <div class="entry-card-grid">
                            <div class="edit-field">
                                <label class="edit-label">title</label>
                                <input class="edit-input" type="text" wire:model="experiences.{{ $i }}.title" placeholder="Software Engineer">
                                @error("experiences.{$i}.title") <div class="edit-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="edit-field">
                                <label class="edit-label">company</label>
                                <input class="edit-input" type="text" wire:model="experiences.{{ $i }}.company" placeholder="Acme Corp">
                            </div>
                            <div class="edit-field">
                                <label class="edit-label">start year</label>
                                <input class="edit-input" type="number" wire:model="experiences.{{ $i }}.start_year" placeholder="2020" min="1970" max="2099">
                                @error("experiences.{$i}.start_year") <div class="edit-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="edit-field">
                                <label class="edit-label">end year</label>
                                <input class="edit-input" type="number" wire:model="experiences.{{ $i }}.end_year" placeholder="present" min="1970" max="2099">
                            </div>
                        </div>
                        <div class="edit-field">
                            <label class="edit-label">description</label>
                            <textarea class="edit-input edit-textarea" wire:model="experiences.{{ $i }}.description" placeholder="What did you do there?" style="min-height: 60px;"></textarea>
                        </div>
                    </div>
                @endforeach

                <button class="add-entry-btn" wire:click="addExperience">+ add experience</button>

                {{-- Projects --}}
                <div class="edit-section-title">projects</div>

                @foreach ($projects as $i => $proj)
                    <div class="entry-card">
                        <button class="entry-remove" wire:click="removeProject({{ $i }})" title="remove">×</button>
                        <div class="edit-field">
                            <label class="edit-label">title</label>
                            <input class="edit-input" type="text" wire:model="projects.{{ $i }}.title" placeholder="My Cool Project">
                            @error("projects.{$i}.title") <div class="edit-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="edit-field">
                            <label class="edit-label">url</label>
                            <input class="edit-input" type="url" wire:model="projects.{{ $i }}.url" placeholder="https://github.com/you/project">
                            @error("projects.{$i}.url") <div class="edit-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="edit-field">
                            <label class="edit-label">description</label>
                            <textarea class="edit-input edit-textarea" wire:model="projects.{{ $i }}.description" placeholder="What does it do?" style="min-height: 60px;"></textarea>
                        </div>
                    </div>
                @endforeach

                <button class="add-entry-btn" wire:click="addProject">+ add project</button>

                {{-- Socials --}}
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

                {{-- Notification Preferences --}}
                <div class="edit-section-title">notification preferences</div>

                <div class="edit-field">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" wire:model="prefRsvpConfirmation">
                        <span class="edit-label" style="text-transform:none; letter-spacing:0;">RSVP confirmations</span>
                    </label>
                    <div style="font-size:11px; color:var(--fg-mute); margin-top:2px;">Receive a confirmation email when you RSVP for an event.</div>
                </div>

                <div class="edit-field">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" wire:model="prefAnnouncements">
                        <span class="edit-label" style="text-transform:none; letter-spacing:0;">Event announcements</span>
                    </label>
                    <div style="font-size:11px; color:var(--fg-mute); margin-top:2px;">Get notified when a new meetup is published.</div>
                </div>

                <div class="edit-field">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" wire:model.live="prefRemindersEnabled">
                        <span class="edit-label" style="text-transform:none; letter-spacing:0;">Reminders</span>
                    </label>
                    <div style="font-size:11px; color:var(--fg-mute); margin-top:2px;">Remind me before a meetup I've RSVP'd for.</div>

                    @if($prefRemindersEnabled)
                        <div style="display:flex; gap:16px; margin-top:8px; padding-left:4px;">
                            <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-size:12px; color:var(--fg-dim);">
                                <input type="checkbox" wire:model="prefReminderTiming" value="24h">
                                24 hours before
                            </label>
                            <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-size:12px; color:var(--fg-dim);">
                                <input type="checkbox" wire:model="prefReminderTiming" value="1h">
                                1 hour before
                            </label>
                        </div>
                        @error('prefReminderTiming') <div class="edit-error">{{ $message }}</div> @enderror
                    @endif
                </div>

                <button class="btn btn-primary" wire:click="save" style="width: 100%; justify-content: center;">
                    <span wire:loading.remove wire:target="save">SAVE PROFILE →</span>
                    <span wire:loading wire:target="save">SAVING...</span>
                </button>

            </div>
        </div>
    </div>
</div>
