<div>
    <style>
        .members-page { max-width: 1200px; margin: 0 auto; padding: 40px 32px; }
        .members-header { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 32px; border-bottom: 2px solid var(--fg); padding-bottom: 24px; }
        .members-header h1 { font-size: 40px; font-weight: 800; letter-spacing: -0.02em; margin: 0; }
        .members-search {
            padding: 8px 12px; border: 2px solid var(--fg); background: var(--bg);
            color: var(--fg); font-family: var(--font-mono); font-size: 13px; outline: none; width: 260px;
        }
        .members-search:focus { border-color: var(--accent); }
        .members-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
        .member-card {
            border: 2px solid var(--fg); padding: 16px;
            text-decoration: none; color: inherit;
            transition: box-shadow 120ms, transform 120ms;
        }
        .member-card:hover { box-shadow: var(--shadow-2); transform: translate(-2px, -2px); background: transparent; color: inherit; }
        .member-avatar {
            width: 48px; height: 48px; border: 2px solid var(--fg);
            background: var(--surface-2); display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 18px; color: var(--accent); margin-bottom: 12px; overflow: hidden;
        }
        .member-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .member-name { font-weight: 700; font-size: 15px; margin-bottom: 2px; }
        .member-handle { font-size: 11px; color: var(--accent); letter-spacing: 0.04em; margin-bottom: 6px; }
        .member-headline { font-size: 12px; color: var(--fg-dim); }
        @media (max-width: 640px) { .members-header { flex-direction: column; gap: 16px; } .members-search { width: 100%; } }
    </style>

    <div class="members-page">
        <div class="members-header">
            <h1><span style="color: var(--accent);">›</span> members</h1>
            <input
                class="members-search"
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="search members..."
            >
        </div>

        @if ($members->isEmpty())
            <p style="color: var(--fg-mute); font-size: 14px;">No members found.</p>
        @else
            <div class="members-grid">
                @foreach ($members as $member)
                    @php $initials = strtoupper(substr($member->name, 0, 1)) . strtoupper(substr(strstr($member->name, ' '), 1, 1)); @endphp
                    <a href="/members/{{ $member->username }}" class="member-card">
                        <div class="member-avatar">
                            @if ($member->avatar_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($member->avatar_path) }}" alt="{{ $member->name }}">
                            @else
                                {{ $initials }}
                            @endif
                        </div>
                        <div class="member-name">{{ $member->name }}</div>
                        <div class="member-handle">@{{ $member->username }}</div>
                        @if ($member->headline)
                            <div class="member-headline">{{ $member->headline }}</div>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
