<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('projects:recalculate-spent')]
#[Description('Recalculate spent amounts for all active projects from GL entries.')]
class RecalculateProjectSpent extends Command
{
    public function handle(ProjectService $projectService): int
    {
        $projects = Project::query()->active()->get();

        foreach ($projects as $project) {
            $projectService->recalculateSpent($project);
            $this->line("Recalculated spent for project {$project->no} — {$project->name}.");
        }

        $this->info("Recalculated {$projects->count()} project(s).");

        return Command::SUCCESS;
    }
}
