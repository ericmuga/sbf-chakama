<?php

namespace App\Events;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Project $project,
        public ?ProjectStatus $fromStatus,
        public ProjectStatus $toStatus,
        public User $changedBy,
        public ?string $reason
    ) {}
}
