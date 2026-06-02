<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('supplier_product_prices')) {
            return;
        }

        Schema::table('supplier_product_prices', function (Blueprint $table) {
            $drops = [];
            foreach (['today_price', 'suggested_price'] as $column) {
                if (Schema::hasColumn('supplier_product_prices', $column)) {
                    $drops[] = $column;
                }
            }
            if ($drops) {
                $table->dropColumn($drops);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('supplier_product_prices')) {
            return;
        }

        Schema::table('supplier_product_prices', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_product_prices', 'today_price')) {
                $table->decimal('today_price', 15, 2)->default(0)->after('min_price');
            }
            if (!Schema::hasColumn('supplier_product_prices', 'suggested_price')) {
                $table->decimal('suggested_price', 15, 2)->default(0)->after('today_price');
            }
        });
    }
};
