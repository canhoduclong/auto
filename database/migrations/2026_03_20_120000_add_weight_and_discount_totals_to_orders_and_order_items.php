<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'total_discount')) {
                $table->decimal('total_discount', 15, 2)->default(0)->after('total');
            }

            if (!Schema::hasColumn('orders', 'total_weight')) {
                $table->decimal('total_weight', 12, 3)->default(0)->after('total_discount');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'base_price')) {
                $table->decimal('base_price', 15, 2)->nullable()->after('price');
            }

            if (!Schema::hasColumn('order_items', 'unit_discount')) {
                $table->decimal('unit_discount', 15, 2)->default(0)->after('base_price');
            }

            if (!Schema::hasColumn('order_items', 'discount_total')) {
                $table->decimal('discount_total', 15, 2)->default(0)->after('unit_discount');
            }

            if (!Schema::hasColumn('order_items', 'unit_weight')) {
                $table->decimal('unit_weight', 12, 3)->default(0)->after('discount_total');
            }

            if (!Schema::hasColumn('order_items', 'total_weight')) {
                $table->decimal('total_weight', 12, 3)->default(0)->after('unit_weight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $dropColumns = array_filter([
                Schema::hasColumn('order_items', 'base_price') ? 'base_price' : null,
                Schema::hasColumn('order_items', 'unit_discount') ? 'unit_discount' : null,
                Schema::hasColumn('order_items', 'discount_total') ? 'discount_total' : null,
                Schema::hasColumn('order_items', 'unit_weight') ? 'unit_weight' : null,
                Schema::hasColumn('order_items', 'total_weight') ? 'total_weight' : null,
            ]);

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            $dropColumns = array_filter([
                Schema::hasColumn('orders', 'total_discount') ? 'total_discount' : null,
                Schema::hasColumn('orders', 'total_weight') ? 'total_weight' : null,
            ]);

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
