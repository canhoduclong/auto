<?php 
namespace App\Policies;

use App\Models\User;
use App\Models\Category;
use Illuminate\Auth\Access\Response;

class CategoryPolicy
{
    public function viewAny(User $user)
    {
        return $user->isAdmin() || $user->hasPermission('categories.index');
    }

    public function create(User $user)
    {
        return $user->isAdmin() || $user->hasPermission('categories.create') || $user->hasPermission('categories.store');
    }

    public function update(User $user, Category $category)
    {
        return $user->isAdmin() || $user->hasPermission('categories.update') || $user->hasPermission('categories.edit');
    }

    public function delete(User $user, Category $category)
    {
        return $user->isAdmin() || $user->hasPermission('categories.delete') || $user->hasPermission('categories.destroy');
    }
    
}
