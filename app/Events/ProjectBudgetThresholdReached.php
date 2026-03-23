<?php

namespace App\Events;

use App\Models\Project;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectBudgetThresholdReached
{
    use Dispatchable, SerializesModels;

    public function __construct(public Project $project, public float $utilisationPercent) {}
}
