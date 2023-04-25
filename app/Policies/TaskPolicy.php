<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\{
    Response,
    HandlesAuthorization,
};

class TaskPolicy
{
    use HandlesAuthorization;

    public function view(): Response
    {
        return $this->allow();
    }

    public function create(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('create task') ? $this->allow() : $this->deny();
    }

    public function update(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('edit task') ? $this->allow() : $this->deny();
    }

    public function delete(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('delete task') ? $this->allow() : $this->deny();
    }

    public function viewAny(): Response
    {
        return $this->allow();
    }
}
