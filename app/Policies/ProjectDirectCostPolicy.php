<?php

namespace App\Policies;

use App\Enums\DirectCostStatus;
use App\Enums\ProjectMemberRole;
use App\Models\ProjectDirectCost;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProjectDirectCostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $this->isMemberOfAnyProject($user);
    }

    public function view(User $user, ProjectDirectCost $cost): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $cost->project?->members()->where('user_id', $user->id)->exists() ?? false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $this->isMemberOfAnyProject($user);
    }

    public function approve(User $user, ProjectDirectCost $cost): bool
    {
        if ($cost->submitted_by === $user->id) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $cost->project?->members()
            ->where('user_id', $user->id)
            ->wherePivotIn('role', [ProjectMemberRole::Owner->value, ProjectMemberRole::Manager->value])
            ->exists() ?? false;
    }

    public function reject(User $user, ProjectDirectCost $cost): bool
    {
        return $this->approve($user, $cost);
    }

    public function post(User $user, ProjectDirectCost $cost): bool
    {
        return $user->isAdmin();
    }

    public function void(User $user, ProjectDirectCost $cost): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $cost->project?->members()
            ->where('user_id', $user->id)
            ->wherePivot('role', ProjectMemberRole::Owner->value)
            ->exists() ?? false;
    }

    public function delete(User $user, ProjectDirectCost $cost): bool
    {
        return $user->isAdmin() && $cost->status === DirectCostStatus::Pending;
    }

    protected function isMemberOfAnyProject(User $user): bool
    {
        return DB::table('project_members')->where('user_id', $user->id)->exists();
    }
}
