<?php

namespace App\Livewire;

use App\Enums\MeetupStatus;
use App\Models\Meetup;
use App\Models\Rsvp;
use Illuminate\Database\UniqueConstraintViolationException;
use Livewire\Component;

class Terminal extends Component
{
    public string $input = '';

    /** @var array<int, array{type: string, text: string}> */
    public array $history = [];

    /** rsvp-pending|rsvp-name|rsvp-email|null */
    public ?string $rsvpState = null;

    public ?string $rsvpSlug = null;

    public ?string $rsvpName = null;

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

        $this->history[] = ['type' => 'input', 'text' => '$ '.$raw];

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
            ['type' => 'output', 'text' => '  whois             about 518.codes'],
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

    public function render()
    {
        return view('livewire.terminal');
    }
}
