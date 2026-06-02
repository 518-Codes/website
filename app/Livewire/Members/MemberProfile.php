<?php

namespace App\Livewire\Members;

use App\Enums\MeetupStatus;
use App\Models\User;
use Livewire\Component;

class MemberProfile extends Component
{
    public User $member;

    public function mount(string $username): void
    {
        $this->member = User::with(['skills', 'experiences', 'projects'])
            ->where('username', $username)
            ->firstOrFail();
    }

    public function render()
    {
        $attendedMeetups = $this->member->rsvps()
            ->with('meetup')
            ->get()
            ->map(fn ($rsvp) => $rsvp->meetup)
            ->filter()
            ->filter(fn ($meetup) => $meetup->status === MeetupStatus::Published);

        return view('livewire.members.member-profile', [
            'member' => $this->member,
            'attendedMeetups' => $attendedMeetups,
        ])->layout('layouts.app', ['title' => $this->member->name.' · 518.codes']);
    }
}
