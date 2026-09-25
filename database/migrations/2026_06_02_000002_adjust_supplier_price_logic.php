<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplier_products') && !Schema::hasColumn('supplier_products', 'price_calculation_type')) {
            Schema::table('supplier_products', function (Blueprint $table) {
                $table->string('price_calculation_type', 32)
                    ->default('component_based')
                    ->after('active');
            });
        }

        if (Schema::hasTable('supplier_product_prices')) {
            Schema::table('supplier_product_prices', function (Blueprint $table) {
                if (!Schema::hasColumn('supplier_product_prices', 'price_calculation_type')) {
                    $table->string('price_calculation_type', 32)->default('component_based')->after('effective_date');
                }
                if (!Schema::hasColumn('supplier_product_prices', 'purchase_price')) {
                    $table->decimal('purchase_price', 15, 2)->default(0)->after('price_calculation_type');
                }
                if (!Schema::hasColumn('supplier_product_prices', 'suggested_margin')) {
                    $table->decimal('suggested_margin', 15, 2)->default(2000)->after('min_price');
                }
                if (!Schema::hasColumn('supplier_product_prices', 'today_sale_price')) {
                    $table->decimal('today_sale_price', 15, 2)->default(0)->after('suggested_margin');
                }
            });

            if (
                Schema::hasColumn('supplier_product_prices', 'today_sale_price')
                && Schema::hasColumn('supplier_product_prices', 'purchase_price')
                && Schema::hasColumn('supplier_product_prices', 'suggested_margin')
                && Schema::hasColumn('supplier_product_prices', 'price_calculation_type')
            ) {
                DB::table('supplier_product_prices')
                    ->where('today_sale_price', 0)
                    ->update([
                        'price_calculation_type' => 'component_based',
                        'purchase_price' => DB::raw('min_price'),
                        'suggested_margin' => DB::raw('GREATEST(COALESCE(suggested_price, 0) - COALESCE(min_price, 0), 0)'),
                        'today_sale_price' => DB::raw('CASE WHEN COALESCE(suggested_price, 0) > 0 THEN suggested_price ELSE min_price END'),
                    ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('supplier_product_prices')) {
            Schema::table('supplier_product_prices', function (Blueprint $table) {
                $drops = [];
                foreach (['price_calculation_type', 'purchase_price', 'suggested_margin', 'today_sale_price'] as $column) {
                    if (Schema::hasColumn('supplier_product_prices', $column)) {
                        $drops[] = $column;
                    }
                }
                if ($drops) {
                    $table->dropColumn($drops);
                }
            });
        }

        if (Schema::hasTable('supplier_products') && Schema::hasColumn('supplier_products', 'price_calculation_type')) {
            Schema::table('supplier_products', function (Blueprint $table) {
                $table->dropColumn('price_calculation_type');
            });
        }
    }
};
