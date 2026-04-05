<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'charge_foam_box_fee')) {
                $table->boolean('charge_foam_box_fee')->default(false)->after('charge_shipping_fee');
            }

            if (!Schema::hasColumn('orders', 'foam_box_price')) {
                $table->decimal('foam_box_price', 15, 2)->nullable()->after('shipping_fee');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'actual_weight')) {
                $table->decimal('actual_weight', 12, 3)->nullable()->after('total_weight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('orders', 'foam_box_price') ? 'foam_box_price' : null,
                Schema::hasColumn('orders', 'charge_foam_box_fee') ? 'charge_foam_box_fee' : null,
            ]);

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'actual_weight')) {
                $table->dropColumn('actual_weight');
            }
        });
    }
};
