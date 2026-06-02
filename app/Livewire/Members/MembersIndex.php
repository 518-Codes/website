<?php

namespace App\Livewire\Members;

use App\Models\User;
use Livewire\Attributes\Url;
use Livewire\Component;

class MembersIndex extends Component
{
    #[Url]
    public string $search = '';

    public function render()
    {
        $members = User::query()
            ->whereNotNull('username')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->get();

        return view('livewire.members.members-index', ['members' => $members])
            ->layout('layouts.app', ['title' => 'members · 518.codes']);
    }
}
