<?php

namespace App\Policies;

use App\Enums\ShareStatus;
use App\Models\ShareSubscription;
use App\Models\User;

class ShareSubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || ($user->member?->is_chakama ?? false);
    }

    public function view(User $user, ShareSubscription $subscription): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $subscription->member_id === $user->member?->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || ($user->member?->is_chakama ?? false);
    }

    public function update(User $user, ShareSubscription $subscription): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ShareSubscription $subscription): bool
    {
        return $user->isAdmin() && $subscription->status === ShareStatus::PendingPayment;
    }
}
