<?php

namespace App\Livewire\Members;

use App\Models\Member;
use Illuminate\View\View;
use Livewire\Component;

class MemberCard extends Component
{
    public ?Member $member = null;

    public function mount(): void
    {
        $this->member = auth()->user()?->member()
            ->with(['user', 'dependants', 'nextOfKin'])
            ->first();
    }

    public function render(): View
    {
        return view('livewire.members.member-card');
    }
}
