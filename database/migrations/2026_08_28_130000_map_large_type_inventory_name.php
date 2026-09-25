<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $variantId = DB::table('product_variants')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->where(function ($query): void {
                $query->where('products.name', 'like', '%loại bầm%')
                    ->orWhere('product_variants.name', 'like', '%loại bầm%');
            })
            ->whereNull('product_variants.inventory_name')
            ->orderBy('product_variants.id')
            ->value('product_variants.id');

        if ($variantId && ! DB::table('product_variants')->where('inventory_name', 'Loại lớn')->exists()) {
            DB::table('product_variants')->where('id', $variantId)->update(['inventory_name' => 'Loại lớn']);
        }
    }

    public function down(): void
    {
        DB::table('product_variants')->where('inventory_name', 'Loại lớn')->update(['inventory_name' => null]);
    }
};
