<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_dispatch_slip_entries', function (Blueprint $table): void {
            $table->foreignId('warehouse_transfer_id')
                ->nullable()
                ->after('order_transfer_id')
                ->constrained('warehouse_transfers')
                ->restrictOnDelete();
            $table->unique('warehouse_transfer_id', 'dispatch_warehouse_transfer_unique');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_dispatch_slip_entries', function (Blueprint $table): void {
            $table->dropUnique('dispatch_warehouse_transfer_unique');
            $table->dropConstrainedForeignId('warehouse_transfer_id');
        });
    }
};
