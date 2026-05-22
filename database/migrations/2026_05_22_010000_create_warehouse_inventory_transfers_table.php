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
        Schema::create('warehouse_inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_code')->nullable()->unique();
            $table->foreignId('source_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('target_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('pending_receive');
            $table->text('note')->nullable();
            $table->foreignId('export_document_id')->nullable()->constrained('inventory_documents')->nullOnDelete();
            $table->foreignId('import_document_id')->nullable()->constrained('inventory_documents')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'target_warehouse_id'], 'wh_inv_transfer_status_target_idx');
            $table->index(['status', 'source_warehouse_id'], 'wh_inv_transfer_status_source_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_inventory_transfers');
    }
};
