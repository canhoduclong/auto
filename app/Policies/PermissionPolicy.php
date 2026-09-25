<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PermissionPolicy
{
    public function viewAny(User $user) {  
        return $user->hasPermission('permissions.index');
    }

    public function view(User $user, Permission $Permission) {
        return $user->hasPermission('permissions.view');
    }

    public function create(User $user) {
        return $user->hasPermission('permissions.create');
    }

    public function update(User $user, Permission $Permission) {
        return $user->hasPermission('permissions.edit');
    }

    public function delete(User $user, Permission $Permission) {
        return $user->hasPermission('permissions.delete');
    }
}





