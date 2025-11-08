<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\Response;    

class CustomerPolicy
{
    public function import(User $user)
    {
        return $user->hasPermission('customers.import');
    }

    public function export(User $user)
    {
        return $user->hasPermission('customers.export');
    }
    public function viewAny(User $user)
    {
        if($user->hasPermission('customers.index')){
            return Response::allow();    
        }
        return Response::deny('Bạn không có quyền truy cập vào trang này. Vui lòng liên hệ quản trị viên.');
    }

    public function view(User $user, Customer $customer) {
        return $user->hasPermission('customers.view');
    }

    public function create(User $user) {
        return $user->hasPermission('customers.create');
    }

    public function update(User $user, Customer $customer) {
        return $user->hasPermission('customers.edit');
    }

    public function delete(User $user, Customer $customer) {
        return $user->hasPermission('customers.delete');
    }
}





