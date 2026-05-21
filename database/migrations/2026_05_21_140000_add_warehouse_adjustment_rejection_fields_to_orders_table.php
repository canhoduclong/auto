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
            $table->unsignedBigInteger('warehouse_adjustment_rejected_by')->nullable()->after('warehouse_adjustment_confirmed_at');
            $table->timestamp('warehouse_adjustment_rejected_at')->nullable()->after('warehouse_adjustment_rejected_by');
            $table->text('warehouse_adjustment_rejected_reason')->nullable()->after('warehouse_adjustment_rejected_at');

            $table->index('warehouse_adjustment_rejected_at', 'orders_wh_adj_rejected_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_wh_adj_rejected_at_idx');
            $table->dropColumn([
                'warehouse_adjustment_rejected_by',
                'warehouse_adjustment_rejected_at',
                'warehouse_adjustment_rejected_reason',
            ]);
        });
    }
};
