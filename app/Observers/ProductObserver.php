<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\AdminActivityService;

class ProductObserver
{
    public function created(Product $product): void
    {
        AdminActivityService::record(
            'product',
            'created',
            $product,
            'Tao moi san pham',
            'San pham "' . $product->name . '" vua duoc tao.',
            ['product_id' => $product->id, 'name' => $product->name],
            route('products.edit', $product)
        );
    }

    public function updated(Product $product): void
    {
        $action = 'updated';
        $title = 'Cap nhat san pham';
        $message = 'San pham "' . $product->name . '" da duoc cap nhat.';

        if ($product->wasChanged('status')) {
            $isActive = (bool) $product->status;
            $action = $isActive ? 'restored' : 'disabled';
            $title = $isActive ? 'Khoi phuc san pham' : 'Tat san pham';
            $message = $isActive
                ? 'San pham "' . $product->name . '" da duoc khoi phuc.'
                : 'San pham "' . $product->name . '" da bi tat.';
        }

        AdminActivityService::record(
            'product',
            $action,
            $product,
            $title,
            $message,
            ['product_id' => $product->id, 'changes' => $product->getChanges()],
            route('products.edit', $product)
        );
    }

    public function deleted(Product $product): void
    {
        AdminActivityService::record(
            'product',
            'deleted',
            $product,
            'Xoa san pham',
            'San pham "' . $product->name . '" da bi xoa.',
            ['product_id' => $product->id, 'name' => $product->name],
            route('products.index')
        );
    }
}
