<?php

namespace App\Policies;

use App\Enums\ApprovalAction;
use App\Enums\ClaimStatus;
use App\Models\Claim;
use App\Models\User;

class ClaimPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || ($user->member?->is_sbf ?? false);
    }

    public function view(User $user, Claim $claim): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($claim->member?->user_id === $user->id) {
            return true;
        }

        // Current approver can view
        return $claim->approvals
            ->where('step_order', $claim->current_step)
            ->where('action', ApprovalAction::Pending)
            ->where('approver_user_id', $user->id)
            ->isNotEmpty();
    }

    public function create(User $user): bool
    {
        return $user->member?->is_sbf ?? false;
    }

    public function update(User $user, Claim $claim): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $claim->member?->user_id === $user->id
            && in_array($claim->status, [ClaimStatus::Draft]);
    }

    public function delete(User $user, Claim $claim): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $claim->member?->user_id === $user->id
            && $claim->status === ClaimStatus::Draft;
    }

    public function restore(User $user, Claim $claim): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Claim $claim): bool
    {
        return $user->isAdmin();
    }

    public function submit(User $user, Claim $claim): bool
    {
        return $claim->member?->user_id === $user->id
            && $claim->status === ClaimStatus::Draft;
    }

    public function approve(User $user, Claim $claim): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $currentApproval = $claim->approvals
            ->where('step_order', $claim->current_step)
            ->first();

        return $currentApproval?->approver_user_id === $user->id
            && $currentApproval?->action === ApprovalAction::Pending;
    }

    public function reject(User $user, Claim $claim): bool
    {
        return $this->approve($user, $claim);
    }

    public function return(User $user, Claim $claim): bool
    {
        return $this->approve($user, $claim);
    }

    public function convertToPurchase(User $user, Claim $claim): bool
    {
        return $user->isAdmin() && $claim->status === ClaimStatus::Approved;
    }

    public function cancel(User $user, Claim $claim): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $claim->member?->user_id === $user->id
            && in_array($claim->status, [ClaimStatus::Draft, ClaimStatus::Submitted]);
    }
}
