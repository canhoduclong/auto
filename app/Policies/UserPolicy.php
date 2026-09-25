<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function viewAny(User $user) {  
        return $user->hasPermission('customers.index');
    }

    public function view(User $user, User $User) {
        return $user->hasPermission('customers.view');
    }

    public function create(User $user) {
        return $user->hasPermission('customers.create');
    }

    public function update(User $user, User $User) {
        return $user->hasPermission('customers.edit');
    }

    public function delete(User $user, User $User) {
        return $user->hasPermission('customers.delete');
    }
}





