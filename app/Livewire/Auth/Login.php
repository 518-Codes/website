<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public function login(): void
    {
        $this->validate();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            $this->addError('email', 'These credentials do not match our records.');

            return;
        }

        session()->regenerate();

        $this->redirect(session()->pull('url.intended', '/'), navigate: true);
    }

    public function mount(): void
    {
        if (auth()->check()) {
            $this->redirect('/', navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('layouts.app', ['title' => 'login · 518.codes']);
    }
}
