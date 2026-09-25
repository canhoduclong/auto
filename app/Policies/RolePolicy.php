<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RolePolicy
{
    public function viewAny(User $user) {  
        return $user->hasPermission('roles.index');
    }

    public function show(User $user, Role $role) {
        return $user->hasPermission('roles.show');
    }

    public function create(User $user) {
        return $user->hasPermission('roles.create');
    }

    public function update(User $user, Role $role) {
        return $user->hasPermission('roles.update');
    }
    public function edit(User $user, Role $role) {
        return $user->hasPermission('roles.edit');
    } 
    public function delete(User $user, Role $role) {
        return $user->hasPermission('roles.delete');
    }
}





