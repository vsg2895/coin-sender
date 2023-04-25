<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\{
    Response,
    HandlesAuthorization,
};

class AmbassadorPolicy
{
    use HandlesAuthorization;

    public function view(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('view ambassador') ? $this->allow() : $this->deny();
    }

    public function delete(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('delete ambassador') ? $this->allow() : $this->deny();
    }

    public function viewAny(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('view ambassadors') ? $this->allow() : $this->deny();
    }

    public function levelUp(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('level up') ? $this->allow() : $this->deny();
    }
}
