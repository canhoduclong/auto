<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ProductVariant;

class ProductVariantPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasPermission('product-variants.index');
    }

    public function update(User $user, ProductVariant $variant)
    {
        return $user->hasPermission('product-variants.edit');
    }

    public function duplicate(User $user, ProductVariant $variant)
    {
        // Quyền mặc định: cho phép admin hoặc user có quyền 'product-variant.duplicate'
        return $user->hasRole('admin') || $user->hasPermission('product-variant.duplicate');
    }

    public function bulkDelete(User $user)
    {
        return $user->hasPermission('product-variants.bulk-delete');
    }
}
