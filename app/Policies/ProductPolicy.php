<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    public function viewAny(User $user) {  
       return $user->isAdmin() || $user->hasPermission('products.index');
    }

    public function view(User $user, Product $product) {
        return $user->isAdmin() || $user->hasPermission('products.show') || $user->hasPermission('products.view');
    }

    public function show(User $user, Product $product) {
        return $this->view($user, $product);
    }

    public function create(User $user) {
        return $user->isAdmin() || $user->hasPermission('products.create') || $user->hasPermission('products.store');
    }

    public function store(User $user) {
        return $this->create($user);
    }

    public function update(User $user, ?Product $product = null) {
        return $user->isAdmin() || $user->hasPermission('products.update') || $user->hasPermission('products.edit');
    }
    public function edit(User $user, ?Product $product = null) {
        return $this->update($user, $product);
    } 
    public function delete(User $user, Product $product) {
        return $user->isAdmin() || $user->hasPermission('products.delete') || $user->hasPermission('products.destroy');
    }
}





