<?php

namespace App\Policies;

use App\Models\{User, AmbassadorTask};
use Illuminate\Auth\Access\{
    Response,
    HandlesAuthorization,
};

class AmbassadorTaskPolicy
{
    use HandlesAuthorization;

    public function view(): Response
    {
        return $this->allow();
    }

    public function done(User $user, AmbassadorTask $ambassadorTask): Response
    {
        $user->load(['roles']);
        return ($user->id === $ambassadorTask->manager_id
            || $user->hasAnyRole(['Super Admin', 'Project Owner'])
        ) && $user->checkPermissionTo('approve task') ? $this->allow() : $this->deny();
    }

    public function return(User $user, AmbassadorTask $ambassadorTask): Response
    {
        $user->load(['roles']);
        return ($user->id === $ambassadorTask->manager_id
            || $user->hasAnyRole(['Super Admin', 'Project Owner'])
        ) && $user->checkPermissionTo('return task') ? $this->allow() : $this->deny();
    }

    public function takeOnRevision(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('take on revision task') ? $this->allow() : $this->deny();
    }

    public function viewAny(): Response
    {
        return $this->allow();
    }
}
