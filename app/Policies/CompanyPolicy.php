<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\Response;    

class CompanyPolicy
{
    public function viewAny(User $user)
    {
        if($user->hasPermission('companies.index')){
            return Response::allow();    
        }
        return Response::deny('Bạn không có quyền truy cập vào trang này. Vui lòng liên hệ quản trị viên.');
    }

    public function view(User $user, Company $company) {
        return $user->hasPermission('companies.view');
    }

    public function create(User $user) {
        return $user->hasPermission('companies.create');
    }

    public function update(User $user, Company $company) {
        return $user->hasPermission('companies.edit');
    }

    public function delete(User $user, Company $company) {
        return $user->hasPermission('companies.delete');
    }
}