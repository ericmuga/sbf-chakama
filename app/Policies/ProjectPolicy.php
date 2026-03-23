<?php

namespace App\Policies;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $project->members()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $project->members()
            ->where('user_id', $user->id)
            ->wherePivotIn('role', [ProjectMemberRole::Owner->value, ProjectMemberRole::Manager->value])
            ->exists();
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->isAdmin()
            && in_array($project->status, [ProjectStatus::Draft, ProjectStatus::Cancelled]);
    }

    public function changeStatus(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $project->members()
            ->where('user_id', $user->id)
            ->wherePivotIn('role', [ProjectMemberRole::Owner->value, ProjectMemberRole::Manager->value])
            ->exists();
    }

    public function manageMembers(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $project->members()
            ->where('user_id', $user->id)
            ->wherePivot('role', ProjectMemberRole::Owner->value)
            ->exists();
    }

    public function manageCosts(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $project->members()
            ->where('user_id', $user->id)
            ->wherePivotIn('role', [ProjectMemberRole::Owner->value, ProjectMemberRole::Manager->value])
            ->exists();
    }
}
