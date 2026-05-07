<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'stock_alert_status')) {
                // ready: du hang, waiting_stock: thieu hang can leader theo doi khi duyet.
                $table->string('stock_alert_status', 32)
                    ->nullable()
                    ->after('stock_shortage_detail');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'stock_alert_status')) {
                $table->dropColumn('stock_alert_status');
            }
        });
    }
};
