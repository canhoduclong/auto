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
            $table->string('warehouse_adjustment_status', 40)
                ->default('none')
                ->after('stock_alert_status');
            $table->text('warehouse_adjustment_note')->nullable()->after('warehouse_adjustment_status');
            $table->json('warehouse_adjustment_changes')->nullable()->after('warehouse_adjustment_note');
            $table->foreignId('warehouse_adjustment_requested_by')
                ->nullable()
                ->after('warehouse_adjustment_changes')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('warehouse_adjustment_requested_at')
                ->nullable()
                ->after('warehouse_adjustment_requested_by');
            $table->foreignId('warehouse_adjustment_confirmed_by')
                ->nullable()
                ->after('warehouse_adjustment_requested_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('warehouse_adjustment_confirmed_at')
                ->nullable()
                ->after('warehouse_adjustment_confirmed_by');

            $table->index(['warehouse_adjustment_status', 'user_id'], 'orders_wh_adj_status_user_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_wh_adj_status_user_idx');
            $table->dropConstrainedForeignId('warehouse_adjustment_confirmed_by');
            $table->dropColumn('warehouse_adjustment_confirmed_at');
            $table->dropColumn('warehouse_adjustment_requested_at');
            $table->dropConstrainedForeignId('warehouse_adjustment_requested_by');
            $table->dropColumn('warehouse_adjustment_changes');
            $table->dropColumn('warehouse_adjustment_note');
            $table->dropColumn('warehouse_adjustment_status');
        });
    }
};
