<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\{
    Response,
    HandlesAuthorization,
};

class MyTeamPolicy
{
    use HandlesAuthorization;

    public function delete(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('delete project member') ? $this->allow() : $this->deny();
    }

    public function viewAny(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('view team') ? $this->allow() : $this->deny();
    }

    public function assignProjectMember(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('assign project member') ? $this->allow() : $this->deny();
    }

    public function assignCustomProjectMember(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('assign custom project member') ? $this->allow() : $this->deny();
    }
}
