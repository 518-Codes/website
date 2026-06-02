<?php

namespace App\Livewire;

use App\Enums\MeetupStatus;
use App\Models\Meetup;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class EventDetail extends Component
{
    public Meetup $meetup;

    public bool $rsvpd = false;

    public bool $showPasswordPrompt = false;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|min:8')]
    public string $newPassword = '';

    public function mount(string $slug): void
    {
        $this->meetup = Meetup::with(['tags', 'rsvps.user', 'scheduleItems', 'images'])
            ->where('slug', $slug)
            ->where('status', MeetupStatus::Published)
            ->firstOrFail();
    }

    public function rsvp(): void
    {
        $this->validate(['name' => 'required|string|max:255', 'email' => 'required|email|max:255']);

        $existingUser = User::where('email', $this->email)->first();

        try {
            $this->meetup->rsvps()->create([
                'name' => $this->name,
                'email' => $this->email,
                'user_id' => $existingUser?->id,
            ]);
        } catch (UniqueConstraintViolationException) {
            $this->addError('email', 'This email is already registered for this event.');

            return;
        }

        $this->rsvpd = true;
        $this->meetup->load('rsvps.user');

        if ($existingUser === null) {
            $this->showPasswordPrompt = true;
        }
    }

    public function createAccount(): void
    {
        $this->validateOnly('newPassword');

        $username = $this->generateUsername($this->name);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'username' => $username,
            'password' => Hash::make($this->newPassword),
        ]);

        Auth::login($user);

        Rsvp::where('meetup_id', $this->meetup->id)
            ->where('email', $this->email)
            ->update(['user_id' => $user->id]);

        $this->showPasswordPrompt = false;
    }

    public function skipAccountCreation(): void
    {
        $this->showPasswordPrompt = false;
    }

    private function generateUsername(string $name): string
    {
        $base = Str::slug($name);
        $username = $base;
        $i = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base.$i;
            $i++;
        }

        return $username;
    }

    public function render()
    {
        return view('livewire.event-detail')
            ->layout('layouts.app', [
                'title' => $this->meetup->title.' · 518.codes',
            ]);
    }
}
