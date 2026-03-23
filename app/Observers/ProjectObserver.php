<?php

namespace App\Observers;

use App\Events\ProjectBudgetThresholdReached;
use App\Models\Project;

class ProjectObserver
{
    public function updated(Project $project): void
    {
        if (! $project->wasChanged('spent') || $project->budget <= 0) {
            return;
        }

        $previousSpent = (float) $project->getOriginal('spent');
        $currentSpent = (float) $project->spent;
        $budget = (float) $project->budget;

        $previousPercent = ($previousSpent / $budget) * 100;
        $currentPercent = ($currentSpent / $budget) * 100;

        foreach ([80.0, 100.0] as $threshold) {
            if ($previousPercent < $threshold && $currentPercent >= $threshold) {
                ProjectBudgetThresholdReached::dispatch($project, $currentPercent);
                break;
            }
        }
    }
}
