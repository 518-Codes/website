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
use Livewire\Component;

class Terminal extends Component
{
    public string $input = '';

    /** @var array<int, array{type: string, text: string}> */
    public array $history = [];

    /** rsvp-pending|rsvp-name|rsvp-email|login-email|login-password|register-name|register-username|register-email|register-password|null */
    public ?string $rsvpState = null;

    public ?string $rsvpSlug = null;

    public ?string $rsvpName = null;

    public ?string $loginEmail = null;

    public ?string $registerName = null;

    public ?string $registerUsername = null;

    public ?string $registerEmail = null;

    public ?string $redirectTo = null;

    public function mount(): void
    {
        $this->history = [
            ['type' => 'system', 'text' => '518.codes terminal v1.0.0 — type `help` to get started'],
        ];
    }

    public function submit(): void
    {
        $raw = trim($this->input);
        $this->input = '';

        if ($raw === '') {
            return;
        }

        $isPassword = in_array($this->rsvpState, ['login-password', 'register-password']);
        $this->history[] = ['type' => 'input', 'text' => '$ '.($isPassword ? str_repeat('•', strlen($raw)) : $raw)];

        if ($this->rsvpState === 'rsvp-name') {
            if ($raw === '') {
                $this->history[] = ['type' => 'error', 'text' => 'Name cannot be empty.'];

                return;
            }
            $this->rsvpName = $raw;
            $this->rsvpState = 'rsvp-email';
            $this->history[] = ['type' => 'prompt', 'text' => 'enter your email:'];

            return;
        }

        if ($this->rsvpState === 'rsvp-email') {
            if (! filter_var($raw, FILTER_VALIDATE_EMAIL)) {
                $this->history[] = ['type' => 'error', 'text' => 'That doesn\'t look like a valid email. Try again:'];
                $this->history[] = ['type' => 'prompt', 'text' => 'enter your email:'];

                return;
            }

            $meetup = Meetup::where('slug', $this->rsvpSlug)
                ->where('status', MeetupStatus::Published)
                ->first();

            if (! $meetup) {
                $this->history[] = ['type' => 'error', 'text' => 'Event not found or no longer published.'];
                $this->resetRsvp();

                return;
            }

            try {
                $meetup->rsvps()->create(['name' => $this->rsvpName, 'email' => $raw]);
                $this->history[] = ['type' => 'success', 'text' => '✓ You\'re going to "'.$meetup->title.'"! See you there.'];
            } catch (UniqueConstraintViolationException) {
                $this->history[] = ['type' => 'error', 'text' => 'That email is already registered for this event.'];
            }

            $this->resetRsvp();

            return;
        }

        if ($this->rsvpState === 'login-email') {
            if (! filter_var($raw, FILTER_VALIDATE_EMAIL)) {
                $this->history[] = ['type' => 'error', 'text' => 'Enter a valid email address:'];
                $this->history[] = ['type' => 'prompt', 'text' => 'email:'];

                return;
            }
            $this->loginEmail = $raw;
            $this->rsvpState = 'login-password';
            $this->history[] = ['type' => 'prompt', 'text' => 'password:'];

            return;
        }

        if ($this->rsvpState === 'login-password') {
            $user = User::where('email', $this->loginEmail)->first();

            if ($user && Hash::check($raw, $user->password)) {
                session()->put(Auth::guard()->getName(), $user->getAuthIdentifier());
                Auth::setUser($user);
                $this->history[] = ['type' => 'success', 'text' => '✓ Logged in as '.$user->username.'. Welcome back.'];
                $this->history[] = ['type' => 'dim', 'text' => 'redirecting to profile...'];
                $this->redirectTo = '/members/'.$user->username;
            } else {
                $this->history[] = ['type' => 'error', 'text' => 'Invalid email or password.'];
            }
            $this->loginEmail = null;
            $this->rsvpState = null;

            return;
        }

        if ($this->rsvpState === 'register-name') {
            if (strlen($raw) < 2) {
                $this->history[] = ['type' => 'error', 'text' => 'Name must be at least 2 characters:'];
                $this->history[] = ['type' => 'prompt', 'text' => 'your name:'];

                return;
            }
            $this->registerName = $raw;
            $this->registerUsername = Str::slug($raw);
            $this->rsvpState = 'register-username';
            $this->history[] = ['type' => 'prompt', 'text' => 'choose a username (suggested: '.$this->registerUsername.'):'];

            return;
        }

        if ($this->rsvpState === 'register-username') {
            $username = $raw === '' ? $this->registerUsername : Str::slug($raw);
            if (strlen($username) < 2) {
                $this->history[] = ['type' => 'error', 'text' => 'Username must be at least 2 characters:'];
                $this->history[] = ['type' => 'prompt', 'text' => 'choose a username:'];

                return;
            }
            if (User::where('username', $username)->exists()) {
                $this->history[] = ['type' => 'error', 'text' => 'Username "'.$username.'" is taken. Try another:'];
                $this->history[] = ['type' => 'prompt', 'text' => 'choose a username:'];

                return;
            }
            $this->registerUsername = $username;
            $this->rsvpState = 'register-email';
            $this->history[] = ['type' => 'prompt', 'text' => 'email:'];

            return;
        }

        if ($this->rsvpState === 'register-email') {
            if (! filter_var($raw, FILTER_VALIDATE_EMAIL)) {
                $this->history[] = ['type' => 'error', 'text' => 'Enter a valid email address:'];
                $this->history[] = ['type' => 'prompt', 'text' => 'email:'];

                return;
            }
            if (User::where('email', $raw)->exists()) {
                $this->history[] = ['type' => 'error', 'text' => 'That email is already registered. Try `login` instead.'];
                $this->resetRegister();

                return;
            }
            $this->registerEmail = $raw;
            $this->rsvpState = 'register-password';
            $this->history[] = ['type' => 'prompt', 'text' => 'password (min 8 chars):'];

            return;
        }

        if ($this->rsvpState === 'register-password') {
            if (strlen($raw) < 8) {
                $this->history[] = ['type' => 'error', 'text' => 'Password must be at least 8 characters:'];
                $this->history[] = ['type' => 'prompt', 'text' => 'password:'];

                return;
            }
            $user = User::create([
                'name' => $this->registerName,
                'username' => $this->registerUsername,
                'email' => $this->registerEmail,
                'password' => Hash::make($raw),
            ]);
            session()->put(Auth::guard()->getName(), $user->getAuthIdentifier());
            Auth::setUser($user);
            $this->history[] = ['type' => 'success', 'text' => '✓ Account created! Welcome, '.$user->username.'.'];
            $this->history[] = ['type' => 'dim', 'text' => 'redirecting to profile...'];
            $this->redirectTo = '/members/'.$user->username.'/edit';
            $this->resetRegister();

            return;
        }

        $parts = preg_split('/\s+/', $raw, 2);
        $command = strtolower($parts[0]);
        $args = $parts[1] ?? '';

        match ($command) {
            'help' => $this->cmdHelp(),
            'events' => $this->cmdEvents(),
            'directions' => $this->cmdDirections($args),
            'host' => $this->cmdHost(),
            'whois' => $this->cmdWhois(),
            'rsvp' => $this->cmdRsvp($args),
            'login' => $this->cmdLogin(),
            'logout' => $this->cmdLogout(),
            'register' => $this->cmdRegister(),
            'clear' => $this->cmdClear(),
            default => $this->history[] = ['type' => 'error', 'text' => 'Unknown command: '.$command.'. Type `help` for available commands.'],
        };
    }

    private function cmdHelp(): void
    {
        $lines = [
            ['type' => 'output', 'text' => 'available commands:'],
            ['type' => 'output', 'text' => '  help              show this message'],
            ['type' => 'output', 'text' => '  events            list upcoming events'],
            ['type' => 'output', 'text' => '  directions <slug> get directions to an event'],
            ['type' => 'output', 'text' => '  host              propose an event'],
            ['type' => 'output', 'text' => '  rsvp <slug>       RSVP to an event'],
            ['type' => 'output', 'text' => '  whois             about 518.codes (or yourself)'],
            ['type' => 'output', 'text' => '  login             log in to your account'],
            ['type' => 'output', 'text' => '  register          create a new account'],
            ['type' => 'output', 'text' => '  logout            log out'],
            ['type' => 'output', 'text' => '  clear             clear the terminal'],
        ];

        foreach ($lines as $line) {
            $this->history[] = $line;
        }
    }

    private function cmdEvents(): void
    {
        $meetups = Meetup::published()->upcoming()->orderBy('starts_at')->get();

        if ($meetups->isEmpty()) {
            $this->history[] = ['type' => 'output', 'text' => '// no upcoming events scheduled'];
            $this->history[] = ['type' => 'dim', 'text' => 'want to host one? visit 518.codes/#host'];

            return;
        }

        $this->history[] = ['type' => 'output', 'text' => sprintf('%-32s %-20s %s', 'EVENT', 'DATE', 'SLUG')];
        $this->history[] = ['type' => 'dim', 'text' => str_repeat('─', 72)];

        foreach ($meetups as $meetup) {
            $this->history[] = [
                'type' => 'output',
                'text' => sprintf(
                    '%-32s %-20s %s',
                    mb_strimwidth($meetup->title, 0, 30, '…'),
                    $meetup->starts_at->format('M j, Y g:i a'),
                    $meetup->slug,
                ),
            ];
        }

        $this->history[] = ['type' => 'dim', 'text' => $meetups->count().' event(s) · rsvp <slug> to register'];
    }

    private function cmdHost(): void
    {
        $this->history[] = ['type' => 'output', 'text' => '// propose an event'];
        $this->history[] = ['type' => 'output', 'text' => '   Got a topic, a venue, or just a Tuesday?'];
        $this->history[] = ['type' => 'output', 'text' => '   Submit a one-paragraph proposal and we\'ll handle the rest.'];
        $this->history[] = ['type' => 'dim', 'text' => str_repeat('─', 72)];
        $this->history[] = ['type' => 'link', 'text' => route('host')];
    }

    private function cmdWhois(): void
    {
        $user = Auth::user();

        if ($user) {
            $rsvpCount = $user->rsvps()->count();
            $skillList = $user->skills->pluck('name')->join(', ');

            $lines = [
                ['type' => 'accent', 'text' => $user->username],
                ['type' => 'output', 'text' => ''],
                ['type' => 'output', 'text' => 'name      '.$user->name],
                ['type' => 'output', 'text' => 'email     '.$user->email],
            ];

            if ($user->headline) {
                $lines[] = ['type' => 'output', 'text' => 'headline  '.$user->headline];
            }
            if ($user->company) {
                $lines[] = ['type' => 'output', 'text' => 'company   '.$user->company];
            }
            if ($skillList) {
                $lines[] = ['type' => 'output', 'text' => 'skills    '.$skillList];
            }

            $lines[] = ['type' => 'output', 'text' => ''];
            $lines[] = ['type' => 'dim', 'text' => 'events attended: '.$rsvpCount.' · profile: /members/'.$user->username];

            foreach ($lines as $line) {
                $this->history[] = $line;
            }

            return;
        }

        $lines = [
            ['type' => 'accent', 'text' => '518.codes'],
            ['type' => 'output', 'text' => ''],
            ['type' => 'output', 'text' => 'A community of software developers in New York\'s Capital Region.'],
            ['type' => 'output', 'text' => 'Free, volunteer-run, and always open to first-timers.'],
            ['type' => 'output', 'text' => ''],
            ['type' => 'output', 'text' => 'Meetups, demo nights, and study groups for people who'],
            ['type' => 'output', 'text' => 'write code in the 518. Show up. Say hi. Write code.'],
            ['type' => 'output', 'text' => ''],
            ['type' => 'dim', 'text' => implode(' · ', config('cities'))],
        ];

        foreach ($lines as $line) {
            $this->history[] = $line;
        }
    }

    private function cmdDirections(string $slug): void
    {
        if ($slug === '') {
            $this->history[] = ['type' => 'error', 'text' => 'Usage: directions <event-slug>  (run `events` to see slugs)'];

            return;
        }

        $meetup = Meetup::where('slug', $slug)
            ->where('status', MeetupStatus::Published)
            ->first();

        if (! $meetup) {
            $this->history[] = ['type' => 'error', 'text' => 'No published event found with slug "'.$slug.'".'];
            $this->history[] = ['type' => 'dim', 'text' => 'Run `events` to see available slugs.'];

            return;
        }

        $mapsUrl = 'https://www.google.com/maps/dir/?api=1&destination='.urlencode($meetup->location);

        $this->history[] = ['type' => 'output', 'text' => '// directions to: '.$meetup->title];
        $this->history[] = ['type' => 'output', 'text' => '   '.$meetup->location];
        $this->history[] = ['type' => 'output', 'text' => '   '.$meetup->starts_at->format('D, M j, Y · g:i a')];
        $this->history[] = ['type' => 'dim', 'text' => str_repeat('─', 72)];
        $this->history[] = ['type' => 'link', 'text' => $mapsUrl];
        $this->history[] = ['type' => 'dim', 'text' => '↑ open in browser for turn-by-turn directions'];
    }

    private function cmdRsvp(string $slug): void
    {
        if ($slug === '') {
            $this->history[] = ['type' => 'error', 'text' => 'Usage: rsvp <event-slug>  (run `events` to see slugs)'];

            return;
        }

        $meetup = Meetup::where('slug', $slug)
            ->where('status', MeetupStatus::Published)
            ->first();

        if (! $meetup) {
            $this->history[] = ['type' => 'error', 'text' => 'No published event found with slug "'.$slug.'".'];
            $this->history[] = ['type' => 'dim', 'text' => 'Run `events` to see available slugs.'];

            return;
        }

        $this->rsvpSlug = $slug;
        $this->rsvpState = 'rsvp-name';

        $this->history[] = ['type' => 'output', 'text' => 'RSVPing to: '.$meetup->title];
        $this->history[] = ['type' => 'output', 'text' => $meetup->starts_at->format('D, M j, Y · g:i a').' · '.$meetup->location];
        $this->history[] = ['type' => 'prompt', 'text' => 'enter your name:'];
    }

    private function cmdLogin(): void
    {
        if (Auth::check()) {
            $this->history[] = ['type' => 'error', 'text' => 'You are already logged in as '.Auth::user()->username.'. Run `logout` first.'];

            return;
        }

        $this->rsvpState = 'login-email';
        $this->history[] = ['type' => 'output', 'text' => '// login to 518.codes'];
        $this->history[] = ['type' => 'prompt', 'text' => 'email:'];
    }

    private function cmdLogout(): void
    {
        if (! Auth::check()) {
            $this->history[] = ['type' => 'error', 'text' => 'You are not logged in.'];

            return;
        }

        $username = Auth::user()->username;
        Auth::logout();
        $this->history[] = ['type' => 'success', 'text' => '✓ Logged out. See you next time, '.$username.'.'];
    }

    private function cmdRegister(): void
    {
        if (Auth::check()) {
            $this->history[] = ['type' => 'error', 'text' => 'You are already logged in as '.Auth::user()->username.'.'];

            return;
        }

        $this->rsvpState = 'register-name';
        $this->history[] = ['type' => 'output', 'text' => '// create a 518.codes account'];
        $this->history[] = ['type' => 'prompt', 'text' => 'your name:'];
    }

    private function cmdClear(): void
    {
        $this->history = [
            ['type' => 'system', 'text' => '518.codes terminal v1.0.0 — type `help` to get started'],
        ];
    }

    private function resetRsvp(): void
    {
        $this->rsvpState = null;
        $this->rsvpSlug = null;
        $this->rsvpName = null;
    }

    private function resetRegister(): void
    {
        $this->rsvpState = null;
        $this->registerName = null;
        $this->registerUsername = null;
        $this->registerEmail = null;
    }

    public function render()
    {
        return view('livewire.terminal');
    }
}
