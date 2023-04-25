<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\{
    Response,
    HandlesAuthorization,
};

class AmbassadorActivityPolicy
{
    use HandlesAuthorization;

    public function delete(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('delete activity') ? $this->allow() : $this->deny();
    }

    public function approve(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('approve activity') ? $this->allow() : $this->deny();
    }

    public function decline(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('decline activity') ? $this->allow() : $this->deny();
    }
}
