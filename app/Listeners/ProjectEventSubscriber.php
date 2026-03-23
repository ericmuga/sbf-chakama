<?php

namespace App\Listeners;

use App\Enums\ProjectMemberRole;
use App\Events\DirectCostApproved;
use App\Events\DirectCostPosted;
use App\Events\DirectCostRejected;
use App\Events\MemberAddedToProject;
use App\Events\ProjectBudgetThresholdReached;
use App\Events\ProjectStatusChanged;
use App\Notifications\AddedToProjectNotification;
use App\Notifications\BudgetThresholdNotification;
use App\Notifications\DirectCostActionNotification;
use App\Notifications\ProjectStatusChangedNotification;
use Illuminate\Events\Dispatcher;

class ProjectEventSubscriber
{
    public function handleProjectStatusChanged(ProjectStatusChanged $event): void
    {
        $project = $event->project->load('members');

        $project->members
            ->reject(fn ($member) => $member->id === $event->changedBy->id)
            ->each(fn ($member) => $member->notify(
                new ProjectStatusChangedNotification(
                    $project,
                    $event->fromStatus,
                    $event->toStatus,
                    $event->changedBy,
                    $event->reason,
                )
            ));
    }

    public function handleMemberAdded(MemberAddedToProject $event): void
    {
        $event->member->notify(
            new AddedToProjectNotification($event->project, $event->role, $event->assigner)
        );
    }

    public function handleDirectCostApproved(DirectCostApproved $event): void
    {
        $cost = $event->cost->load('submitter');

        $cost->submitter?->notify(new DirectCostActionNotification($cost, 'approved'));
    }

    public function handleDirectCostRejected(DirectCostRejected $event): void
    {
        $cost = $event->cost->load('submitter');

        $cost->submitter?->notify(new DirectCostActionNotification($cost, 'rejected'));
    }

    public function handleDirectCostPosted(DirectCostPosted $event): void
    {
        $cost = $event->cost->load('submitter');

        $cost->submitter?->notify(new DirectCostActionNotification($cost, 'posted'));
    }

    public function handleBudgetThresholdReached(ProjectBudgetThresholdReached $event): void
    {
        $project = $event->project->load('members');

        $project->members
            ->filter(fn ($member) => in_array(
                $member->pivot->role,
                [ProjectMemberRole::Owner, ProjectMemberRole::Manager]
            ))
            ->each(fn ($member) => $member->notify(
                new BudgetThresholdNotification($project, $event->utilisationPercent)
            ));
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            ProjectStatusChanged::class => 'handleProjectStatusChanged',
            MemberAddedToProject::class => 'handleMemberAdded',
            DirectCostApproved::class => 'handleDirectCostApproved',
            DirectCostRejected::class => 'handleDirectCostRejected',
            DirectCostPosted::class => 'handleDirectCostPosted',
            ProjectBudgetThresholdReached::class => 'handleBudgetThresholdReached',
        ];
    }
}
