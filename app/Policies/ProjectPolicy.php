<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\{
    Response,
    HandlesAuthorization,
};

class ProjectPolicy
{
    use HandlesAuthorization;

    public function view(): Response
    {
        return $this->allow();
    }

    public function viewAny(): Response
    {
        return $this->allow();
    }

    public function delete(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('delete project') ? $this->allow() : $this->deny();
    }

    public function update(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('edit project') ? $this->allow() : $this->deny();
    }

    public function create(User $user): Response
    {
        $user->load(['roles']);
        return $user->checkPermissionTo('create project') ? $this->allow() : $this->deny();
    }
}
