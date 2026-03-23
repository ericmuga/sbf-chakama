<?php

namespace App\Events;

use App\Enums\ProjectMemberRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberAddedToProject
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Project $project,
        public User $member,
        public ProjectMemberRole $role,
        public User $assigner
    ) {}
}
