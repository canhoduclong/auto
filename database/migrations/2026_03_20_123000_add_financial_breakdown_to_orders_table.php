<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'subtotal_amount')) {
                $table->decimal('subtotal_amount', 15, 2)->default(0)->after('total');
            }

            if (!Schema::hasColumn('orders', 'item_discount_total')) {
                $table->decimal('item_discount_total', 15, 2)->default(0)->after('subtotal_amount');
            }

            if (!Schema::hasColumn('orders', 'extra_discount_total')) {
                $table->decimal('extra_discount_total', 15, 2)->default(0)->after('item_discount_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $dropColumns = array_filter([
                Schema::hasColumn('orders', 'subtotal_amount') ? 'subtotal_amount' : null,
                Schema::hasColumn('orders', 'item_discount_total') ? 'item_discount_total' : null,
                Schema::hasColumn('orders', 'extra_discount_total') ? 'extra_discount_total' : null,
            ]);

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
