<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->string('inventory_name', 100)->nullable()->after('sku');
            $table->unique('inventory_name');
        });

        DB::table('product_variants')
            ->whereNull('inventory_name')
            ->where('sku', 'like', 'MOC%')
            ->orderBy('id')
            ->get(['id', 'sku', 'size'])
            ->each(function ($variant): void {
                $size = is_numeric($variant->size) ? (float) $variant->size : null;
                if ($size === null && preg_match('/(\d+(?:[.,]\d+)?)/', (string) $variant->sku, $matches)) {
                    $size = (float) str_replace(',', '.', $matches[1]);
                }
                if ($size === null) {
                    return;
                }

                $formatted = number_format($size, 1, ',', '');
                $inventoryName = str_ends_with($formatted, ',0')
                    ? 'M '.substr($formatted, 0, -2)
                    : 'M '.$formatted;
                if (! DB::table('product_variants')->where('inventory_name', $inventoryName)->exists()) {
                    DB::table('product_variants')->where('id', $variant->id)->update(['inventory_name' => $inventoryName]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropUnique(['inventory_name']);
            $table->dropColumn('inventory_name');
        });
    }
};
