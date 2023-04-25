<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\{
    Response,
    HandlesAuthorization,
};

class AccessPolicy
{
    use HandlesAuthorization;

    public function create(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('create access') ? $this->allow() : $this->deny();
    }

    public function update(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('update access') ? $this->allow() : $this->deny();
    }

    public function delete(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('delete access') ? $this->allow() : $this->deny();
    }

    public function viewAny(User $user): Response
    {
        $user->load(['roles']);
        return $user->hasPermissionTo('view accesses') ? $this->allow() : $this->deny();
    }
}
