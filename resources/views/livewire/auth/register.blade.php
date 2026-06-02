<div>
    <style>
        .auth-page { max-width: 480px; margin: 80px auto; padding: 0 32px; }
        .auth-box { border: 2px solid var(--fg); box-shadow: var(--shadow-2); }
        .auth-head {
            background: var(--fg); color: var(--bg); padding: 8px 14px;
            font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; font-weight: 700;
        }
        .auth-body { padding: 24px; display: flex; flex-direction: column; gap: 14px; }
        .auth-field { display: flex; flex-direction: column; gap: 4px; }
        .auth-label { font-size: 11px; letter-spacing: 0.08em; text-transform: uppercase; color: var(--fg-mute); }
        .auth-input {
            padding: 8px 12px; border: 2px solid var(--fg); background: var(--bg);
            color: var(--fg); font-family: var(--font-mono); font-size: 13px; outline: none;
        }
        .auth-input:focus { border-color: var(--accent); }
        .auth-error { font-size: 11px; color: var(--amber); margin-top: 2px; }
        .auth-footer { font-size: 13px; color: var(--fg-dim); text-align: center; padding: 14px; border-top: 1px solid var(--hairline); }
    </style>

    <div class="auth-page">
        <div class="auth-box">
            <div class="auth-head">// register</div>
            <div class="auth-body">
                <div class="auth-field">
                    <label class="auth-label">name</label>
                    <input class="auth-input" type="text" wire:model="name" placeholder="your full name">
                    @error('name') <div class="auth-error">{{ $message }}</div> @enderror
                </div>
                <div class="auth-field">
                    <label class="auth-label">username</label>
                    <input class="auth-input" type="text" wire:model="username" placeholder="your_handle">
                    @error('username') <div class="auth-error">{{ $message }}</div> @enderror
                </div>
                <div class="auth-field">
                    <label class="auth-label">email</label>
                    <input class="auth-input" type="email" wire:model="email" placeholder="you@example.com">
                    @error('email') <div class="auth-error">{{ $message }}</div> @enderror
                </div>
                <div class="auth-field">
                    <label class="auth-label">password</label>
                    <input class="auth-input" type="password" wire:model="password" placeholder="min 8 chars">
                    @error('password') <div class="auth-error">{{ $message }}</div> @enderror
                </div>
                <div class="auth-field">
                    <label class="auth-label">confirm password</label>
                    <input class="auth-input" type="password" wire:model="password_confirmation">
                </div>
                <button class="btn btn-primary" wire:click="register" style="width: 100%; justify-content: center;">
                    <span wire:loading.remove wire:target="register">CREATE ACCOUNT →</span>
                    <span wire:loading wire:target="register">CREATING...</span>
                </button>
            </div>
            <div class="auth-footer">
                already have an account? <a href="/login">log in</a>
            </div>
        </div>
    </div>
</div>
