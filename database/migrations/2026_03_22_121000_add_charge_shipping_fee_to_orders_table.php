<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'charge_shipping_fee')) {
                $table->boolean('charge_shipping_fee')->default(true)->after('actual_weight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'charge_shipping_fee')) {
                $table->dropColumn('charge_shipping_fee');
            }
        });
    }
};
