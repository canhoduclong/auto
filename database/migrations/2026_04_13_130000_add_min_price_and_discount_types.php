<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_price_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('product_price_rules', 'min_price')) {
                $table->decimal('min_price', 10, 2)->default(0)->after('price');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'discount_type')) {
                $table->string('discount_type', 20)->default('decrease')->after('unit_discount');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'order_discount_type')) {
                $table->string('order_discount_type', 20)->default('decrease')->after('order_discount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'order_discount_type')) {
                $table->dropColumn('order_discount_type');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'discount_type')) {
                $table->dropColumn('discount_type');
            }
        });

        Schema::table('product_price_rules', function (Blueprint $table) {
            if (Schema::hasColumn('product_price_rules', 'min_price')) {
                $table->dropColumn('min_price');
            }
        });
    }
};
