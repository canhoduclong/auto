<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'actual_weight')) {
                $table->decimal('actual_weight', 12, 3)->nullable()->after('total_weight');
            }

            if (!Schema::hasColumn('orders', 'shipping_fee')) {
                $table->decimal('shipping_fee', 15, 2)->nullable()->after('actual_weight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $dropColumns = array_filter([
                Schema::hasColumn('orders', 'shipping_fee') ? 'shipping_fee' : null,
                Schema::hasColumn('orders', 'actual_weight') ? 'actual_weight' : null,
            ]);

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
