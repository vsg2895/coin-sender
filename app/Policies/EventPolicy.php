<?php

namespace App\Policies;

use App\Models\User;

use Illuminate\Auth\Access\{
    Response,
    HandlesAuthorization,
};

class EventPolicy
{
    use HandlesAuthorization;

    public function viewAny(): Response
    {
        return $this->allow();
    }

    public function create(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('create event') ? $this->allow() : $this->deny();
    }
}
