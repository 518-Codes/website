<div>
    <style>
        .profile-page { max-width: 1200px; margin: 0 auto; padding: 40px 32px 0; }
        .profile-crumbs { font-size: 13px; color: var(--fg-mute); margin-bottom: 24px; }
        .profile-crumbs a { color: var(--fg-dim); text-decoration: none; }
        .profile-crumbs a:hover { color: var(--accent); background: transparent; }
        .profile-crumbs .sep { margin: 0 8px; color: var(--accent); }
        .profile-layout { display: grid; grid-template-columns: 1fr 280px; gap: 48px; align-items: start; }
        .profile-name { font-size: 48px; font-weight: 800; letter-spacing: -0.02em; margin: 0 0 8px; }
        .profile-headline { font-size: 18px; color: var(--fg-dim); margin: 0 0 20px; }
        .profile-bio { font-size: 15px; line-height: 1.7; color: var(--fg-dim); max-width: 60ch; }
        .profile-section-title { font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--fg-mute); font-weight: 700; margin: 36px 0 12px; }
        .profile-skills { display: flex; flex-wrap: wrap; gap: 8px; }
        .profile-exp { border: 2px solid var(--fg); margin-bottom: 12px; }
        .profile-exp-head { padding: 10px 14px; border-bottom: 1px solid var(--hairline); }
        .profile-exp-title { font-weight: 700; font-size: 15px; }
        .profile-exp-company { font-size: 13px; color: var(--accent); }
        .profile-exp-years { font-size: 11px; color: var(--fg-mute); margin-top: 2px; }
        .profile-exp-desc { padding: 10px 14px; font-size: 13px; color: var(--fg-dim); }
        .profile-project { border: 2px solid var(--fg); padding: 14px; margin-bottom: 12px; }
        .profile-project-title { font-weight: 700; font-size: 15px; margin-bottom: 4px; }
        .profile-project-desc { font-size: 13px; color: var(--fg-dim); }
        .profile-sidebar-box { border: 2px solid var(--fg); margin-bottom: 16px; box-shadow: var(--shadow-2); }
        .sidebar-head { background: var(--fg); color: var(--bg); padding: 6px 12px; font-size: 10px; letter-spacing: 0.12em; text-transform: uppercase; font-weight: 700; }
        .sidebar-body { padding: 14px; }
        .profile-avatar { width: 100%; aspect-ratio: 1; object-fit: cover; border: 2px solid var(--fg); display: block; margin-bottom: 12px; }
        .profile-avatar-placeholder { width: 100%; aspect-ratio: 1; border: 2px solid var(--fg); background: var(--surface-2); display: flex; align-items: center; justify-content: center; font-size: 48px; font-weight: 800; color: var(--accent); margin-bottom: 12px; }
        .social-link { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--fg-dim); text-decoration: none; margin-bottom: 8px; }
        .social-link:hover { color: var(--accent); background: transparent; }
        .attended-item { font-size: 13px; color: var(--fg-dim); padding: 6px 0; border-bottom: 1px solid var(--hairline); }
        .attended-item:last-child { border-bottom: none; }
        .attended-item a { color: var(--accent); text-decoration: none; }
        .attended-item a:hover { background: var(--accent); color: var(--bg); }
        @media (max-width: 900px) { .profile-layout { grid-template-columns: 1fr; } }
    </style>

    <div class="profile-page">
        <div class="profile-crumbs">
            <a href="/">$ 518.codes</a>
            <span class="sep">/</span>
            <a href="/members">members</a>
            <span class="sep">/</span>
            <span>{{ $member->username }}</span>
        </div>

        <div class="profile-layout">
            <main>
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h1 class="profile-name">{{ $member->name }}</h1>
                        @if ($member->headline)
                            <p class="profile-headline">{{ $member->headline }}</p>
                        @endif
                    </div>
                    @auth
                        @if (auth()->id() === $member->id)
                            <a href="/members/{{ $member->username }}/edit" class="btn btn-ghost" style="font-size: 12px;">edit profile</a>
                        @endif
                    @endauth
                </div>

                @if ($member->bio)
                    <p class="profile-bio">{{ $member->bio }}</p>
                @endif

                @if ($member->skills->isNotEmpty())
                    <div class="profile-section-title">// skills</div>
                    <div class="profile-skills">
                        @foreach ($member->skills as $skill)
                            <span class="chip chip-accent">{{ $skill->name }}</span>
                        @endforeach
                    </div>
                @endif

                @if ($member->experiences->isNotEmpty())
                    <div class="profile-section-title">// experience</div>
                    @foreach ($member->experiences as $exp)
                        <div class="profile-exp">
                            <div class="profile-exp-head">
                                <div class="profile-exp-title">{{ $exp->title }}</div>
                                <div class="profile-exp-company">{{ $exp->company }}</div>
                                <div class="profile-exp-years">
                                    {{ $exp->start_year }} – {{ $exp->end_year ?? 'present' }}
                                </div>
                            </div>
                            @if ($exp->description)
                                <div class="profile-exp-desc">{{ $exp->description }}</div>
                            @endif
                        </div>
                    @endforeach
                @endif

                @if ($member->projects->isNotEmpty())
                    <div class="profile-section-title">// projects</div>
                    @foreach ($member->projects as $project)
                        <div class="profile-project">
                            <div class="profile-project-title">
                                @if ($project->url)
                                    <a href="{{ $project->url }}" target="_blank" rel="noopener">{{ $project->title }}</a>
                                @else
                                    {{ $project->title }}
                                @endif
                            </div>
                            @if ($project->description)
                                <div class="profile-project-desc">{{ $project->description }}</div>
                            @endif
                        </div>
                    @endforeach
                @endif
            </main>

            <aside>
                <div class="profile-sidebar-box">
                    <div class="sidebar-head">// profile</div>
                    <div class="sidebar-body">
                        @if ($member->avatar_path)
                            <img
                                class="profile-avatar"
                                src="{{ \Illuminate\Support\Facades\Storage::url($member->avatar_path) }}"
                                alt="{{ $member->name }}"
                            >
                        @else
                            @php $initials = strtoupper(substr($member->name, 0, 1)) . strtoupper(substr(strstr($member->name, ' '), 1, 1)); @endphp
                            <div class="profile-avatar-placeholder">{{ $initials }}</div>
                        @endif

                        @if ($member->company)
                            <div style="font-size: 13px; color: var(--fg-dim); margin-bottom: 12px;">{{ $member->company }}</div>
                        @endif

                        @if ($member->github_url)
                            <a href="{{ $member->github_url }}" class="social-link" target="_blank" rel="noopener">
                                <span style="color: var(--accent);">›</span> github
                            </a>
                        @endif
                        @if ($member->twitter_url)
                            <a href="{{ $member->twitter_url }}" class="social-link" target="_blank" rel="noopener">
                                <span style="color: var(--accent);">›</span> twitter
                            </a>
                        @endif
                        @if ($member->linkedin_url)
                            <a href="{{ $member->linkedin_url }}" class="social-link" target="_blank" rel="noopener">
                                <span style="color: var(--accent);">›</span> linkedin
                            </a>
                        @endif
                        @if ($member->website_url)
                            <a href="{{ $member->website_url }}" class="social-link" target="_blank" rel="noopener">
                                <span style="color: var(--accent);">›</span> website
                            </a>
                        @endif
                    </div>
                </div>

                @if ($attendedMeetups->isNotEmpty())
                    <div class="profile-sidebar-box">
                        <div class="sidebar-head">// events attended ({{ $attendedMeetups->count() }})</div>
                        <div class="sidebar-body" style="padding: 8px 14px;">
                            @foreach ($attendedMeetups as $meetup)
                                <div class="attended-item">
                                    <a href="{{ route('events.show', $meetup->slug) }}">{{ $meetup->title }}</a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </aside>
        </div>
    </div>
</div>
