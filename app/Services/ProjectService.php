<?php

namespace App\Services;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Events\MemberAddedToProject;
use App\Events\ProjectCreated;
use App\Events\ProjectStatusChanged;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Finance\GlEntry;
use App\Models\Finance\NumberSeries;
use App\Models\Project;
use App\Models\ProjectStatusHistory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectService
{
    public function createProject(array $data, User $creator): Project
    {
        return DB::transaction(function () use ($data, $creator) {
            $no = NumberSeries::generate('PROJ');

            $project = Project::create(array_merge($data, [
                'no' => $no,
                'slug' => Str::slug($data['name']).'-'.time(),
                'status' => ProjectStatus::Draft,
                'number_series_code' => 'PROJ',
                'created_by' => $creator->id,
            ]));

            $project->members()->attach($creator->id, [
                'role' => ProjectMemberRole::Owner->value,
                'assigned_at' => now(),
                'assigned_by' => $creator->id,
            ]);

            ProjectStatusHistory::create([
                'project_id' => $project->id,
                'from_status' => null,
                'to_status' => ProjectStatus::Draft->value,
                'changed_by' => $creator->id,
                'reason' => null,
            ]);

            ProjectCreated::dispatch($project);

            return $project;
        });
    }

    public function updateProject(Project $project, array $data, User $editor): Project
    {
        $project->updated_by = $editor->id;

        if (isset($data['status'])) {
            $newStatus = $data['status'] instanceof ProjectStatus
                ? $data['status']
                : ProjectStatus::from($data['status']);

            if ($newStatus !== $project->status) {
                $this->changeStatus($project, $newStatus, $editor);
            }

            unset($data['status']);
        }

        $project->fill($data);
        $project->save();

        return $project;
    }

    public function changeStatus(Project $project, ProjectStatus $newStatus, User $user, ?string $reason = null): void
    {
        $currentStatus = $project->status;
        $allowed = ProjectStatus::allowedTransitions($currentStatus);

        if (! in_array($newStatus, $allowed, true)) {
            throw new InvalidStatusTransitionException($currentStatus, $newStatus);
        }

        DB::transaction(function () use ($project, $newStatus, $user, $reason, $currentStatus) {
            $project->status = $newStatus;

            if ($newStatus === ProjectStatus::Completed) {
                $project->completed_at = now();
            }

            $project->save();

            ProjectStatusHistory::create([
                'project_id' => $project->id,
                'from_status' => $currentStatus->value,
                'to_status' => $newStatus->value,
                'changed_by' => $user->id,
                'reason' => $reason,
            ]);

            ProjectStatusChanged::dispatch($project, $currentStatus, $newStatus, $user, $reason);
        });
    }

    public function addMember(Project $project, User $user, ProjectMemberRole $role, User $assigner): void
    {
        $project->members()->attach($user->id, [
            'role' => $role->value,
            'assigned_at' => now(),
            'assigned_by' => $assigner->id,
        ]);

        MemberAddedToProject::dispatch($project, $user, $role, $assigner);
    }

    public function removeMember(Project $project, User $user): void
    {
        $ownerCount = $project->members()
            ->wherePivot('role', ProjectMemberRole::Owner->value)
            ->count();

        $isOwner = $project->members()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('role', ProjectMemberRole::Owner->value)
            ->exists();

        if ($isOwner && $ownerCount <= 1) {
            throw new \RuntimeException('Cannot remove the last owner from a project.');
        }

        $project->members()->detach($user->id);
    }

    public function recalculateSpent(Project $project): void
    {
        $spent = GlEntry::where('project_id', $project->id)
            ->selectRaw('COALESCE(SUM(debit_amount), 0) - COALESCE(SUM(credit_amount), 0) as net')
            ->value('net') ?? 0;

        $project->update(['spent' => $spent]);
    }

    public function getBudgetVsActual(Project $project): Collection
    {
        return $project->budgetLines->map(function ($line) use ($project) {
            $actual = GlEntry::where('project_id', $project->id)
                ->where('account_no', $line->gl_account_no)
                ->selectRaw('COALESCE(SUM(debit_amount), 0) - COALESCE(SUM(credit_amount), 0) as net')
                ->value('net') ?? 0;

            $budgeted = (float) $line->budgeted_amount;
            $variance = $budgeted - (float) $actual;
            $variancePercent = $budgeted > 0 ? round($variance / $budgeted * 100, 1) : 0.0;

            return [
                'gl_account_no' => $line->gl_account_no,
                'description' => $line->description,
                'budgeted' => $budgeted,
                'actual' => (float) $actual,
                'variance' => $variance,
                'variance_percent' => $variancePercent,
            ];
        });
    }
}
