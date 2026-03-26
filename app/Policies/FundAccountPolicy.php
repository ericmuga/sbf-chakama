<?php

namespace App\Policies;

use App\Models\FundAccount;
use App\Models\User;

class FundAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, FundAccount $fundAccount): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, FundAccount $fundAccount): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, FundAccount $fundAccount): bool
    {
        return $user->isAdmin();
    }
}
