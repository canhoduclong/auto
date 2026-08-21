<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('warehouse_inventory_transfer_items', 'weight_kg')) {
            Schema::table('warehouse_inventory_transfer_items', function (Blueprint $table) {
                $table->decimal('weight_kg', 12, 3)->nullable()->after('quantity');
            });
        }

        DB::table('warehouse_inventory_transfer_items')
            ->whereNull('weight_kg')
            ->orderBy('id')
            ->chunkById(200, function ($items): void {
                $variantIds = $items->pluck('product_variant_id')->unique()->values();
                $variants = DB::table('product_variants')
                    ->leftJoin('products', 'products.id', '=', 'product_variants.product_id')
                    ->whereIn('product_variants.id', $variantIds)
                    ->get([
                        'product_variants.id',
                        'product_variants.kg as variant_kg',
                        'products.kg as product_kg',
                    ])
                    ->keyBy('id');

                foreach ($items as $item) {
                    $variant = $variants->get($item->product_variant_id);
                    $unitKg = (float) ($variant?->variant_kg ?? 0);
                    if ($unitKg <= 0) {
                        $unitKg = (float) ($variant?->product_kg ?? 0);
                    }
                    if ($unitKg <= 0) {
                        $unitKg = 1;
                    }

                    DB::table('warehouse_inventory_transfer_items')
                        ->where('id', $item->id)
                        ->update(['weight_kg' => round((float) $item->quantity * $unitKg, 3)]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('warehouse_inventory_transfer_items', 'weight_kg')) {
            Schema::table('warehouse_inventory_transfer_items', function (Blueprint $table) {
                $table->dropColumn('weight_kg');
            });
        }
    }
};
