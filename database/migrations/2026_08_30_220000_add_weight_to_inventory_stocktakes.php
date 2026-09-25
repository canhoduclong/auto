<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table): void {
            $table->decimal('weight_kg', 12, 3)->default(0)->after('quantity');
        });

        Schema::table('inventory_stocktake_items', function (Blueprint $table): void {
            $table->decimal('system_weight_kg', 12, 3)->default(0)->after('difference');
            $table->decimal('counted_weight_kg', 12, 3)->default(0)->after('system_weight_kg');
            $table->decimal('weight_difference', 12, 3)->default(0)->after('counted_weight_kg');
        });

        Schema::table('inventory_adjustments', function (Blueprint $table): void {
            $table->decimal('weight_kg', 12, 3)->nullable()->after('quantity');
        });

        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->decimal('weight_kg', 12, 3)->nullable()->after('quantity');
        });

        // There was no weight balance before this migration. Use each variant's
        // configured kg/con as the safest opening estimate until the first count.
        DB::statement(<<<'SQL'
            UPDATE inventories i
            INNER JOIN product_variants pv ON pv.id = i.product_variant_id
            SET i.weight_kg = ROUND(i.quantity * COALESCE(NULLIF(pv.kg, 0), 0), 3)
        SQL);
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->dropColumn('weight_kg');
        });

        Schema::table('inventory_adjustments', function (Blueprint $table): void {
            $table->dropColumn('weight_kg');
        });

        Schema::table('inventory_stocktake_items', function (Blueprint $table): void {
            $table->dropColumn(['system_weight_kg', 'counted_weight_kg', 'weight_difference']);
        });

        Schema::table('inventories', function (Blueprint $table): void {
            $table->dropColumn('weight_kg');
        });
    }
};
