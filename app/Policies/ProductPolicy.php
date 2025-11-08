<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    public function viewAny(User $user) {  
       return $user->hasPermission('products.index');
    }

    public function show(User $user, Product $product) {
        return $user->hasPermission('products.show');
    }

    public function create(User $user) {
        return $user->hasPermission('products.create');
    }

    public function update(User $user, Product $product) {
        return $user->hasPermission('products.update');
    }
    public function edit(User $user, Product $product) {
        return $user->hasPermission('products.edit');
    } 
    public function delete(User $user, Product $product) {
        return $user->hasPermission('products.delete');
    }
}





