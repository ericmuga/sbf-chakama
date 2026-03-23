<?php

namespace App\Console\Commands;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Notifications\ProjectOverdueNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('projects:check-overdue')]
#[Description('Notify project members about overdue projects.')]
class CheckOverdueProjects extends Command
{
    public function handle(): int
    {
        $overdueStatuses = [
            ProjectStatus::Planning->value,
            ProjectStatus::InProgress->value,
            ProjectStatus::OnHold->value,
        ];

        $projects = Project::with('members')
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereIn('status', $overdueStatuses)
            ->get();

        $count = 0;

        foreach ($projects as $project) {
            $notification = new ProjectOverdueNotification($project);

            $project->members->each(fn ($member) => $member->notify($notification));

            $count++;
            $this->line("Notified members of overdue project {$project->no} — {$project->name}.");
        }

        $this->info("Processed {$count} overdue project(s).");

        return Command::SUCCESS;
    }
}
