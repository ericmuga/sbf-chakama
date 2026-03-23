<?php

namespace App\Policies;

use App\Enums\ApprovalAction;
use App\Enums\FundWithdrawalStatus;
use App\Models\FundWithdrawal;
use App\Models\User;

class FundWithdrawalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, FundWithdrawal $withdrawal): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, FundWithdrawal $withdrawal): bool
    {
        return $user->isAdmin() && $withdrawal->status === FundWithdrawalStatus::Draft;
    }

    public function delete(User $user, FundWithdrawal $withdrawal): bool
    {
        return $user->isAdmin() && $withdrawal->status === FundWithdrawalStatus::Draft;
    }

    public function submit(User $user, FundWithdrawal $withdrawal): bool
    {
        return $user->isAdmin();
    }

    public function approve(User $user, FundWithdrawal $withdrawal): bool
    {
        $currentApproval = $withdrawal->approvals
            ->where('step_order', $withdrawal->current_step)
            ->first();

        return $currentApproval?->approver_user_id === $user->id
            && $currentApproval?->action === ApprovalAction::Pending;
    }

    public function reject(User $user, FundWithdrawal $withdrawal): bool
    {
        return $this->approve($user, $withdrawal);
    }
}
