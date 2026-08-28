<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_inventory_transfers', function (Blueprint $table): void {
            $table->foreignId('order_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
            $table->index(['order_id', 'status'], 'wh_inv_transfer_order_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_inventory_transfers', function (Blueprint $table): void {
            $table->dropIndex('wh_inv_transfer_order_status_idx');
            $table->dropConstrainedForeignId('order_id');
        });
    }
};
