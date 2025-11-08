<?php 
namespace App\Policies;

use App\Models\User;
use App\Models\Category;
use Illuminate\Auth\Access\Response;

class CategoryPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasPermission('categories.index');
    }

    public function create(User $user)
    {
        return $user->hasPermission('categories.create');
    }

    public function update(User $user, Category $category)
    {
        return $user->hasPermission('categories.update');
    }

    public function delete(User $user, Category $category)
    {
        return $user->hasPermission('categories.delete');
    }
    
}
