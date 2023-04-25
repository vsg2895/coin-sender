<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\{
    Response,
    HandlesAuthorization,
};

class AmbassadorWalletWithdrawalRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('view withdrawal requests') ? $this->allow() : $this->deny();
    }

    public function approve(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('approve withdrawal request') ? $this->allow() : $this->deny();
    }

    public function decline(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('decline withdrawal request') ? $this->allow() : $this->deny();
    }
}
