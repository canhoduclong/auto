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
        Schema::create('warehouse_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('target_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('shipper_id')->constrained('users')->cascadeOnDelete();

            $table->string('status', 40)->default('pending_shipper_pickup');
            $table->text('note')->nullable();
            $table->text('shipper_pickup_note')->nullable();
            $table->text('shipper_delivery_note')->nullable();
            $table->string('delivery_proof_image')->nullable();

            $table->foreignId('export_document_id')->nullable()->constrained('inventory_documents')->nullOnDelete();
            $table->foreignId('import_document_id')->nullable()->constrained('inventory_documents')->nullOnDelete();

            $table->foreignId('picked_up_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('picked_up_at')->nullable();
            $table->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();

            $table->decimal('packed_total_weight', 12, 3)->nullable();
            $table->decimal('received_total_weight', 12, 3)->nullable();
            $table->decimal('weight_loss', 12, 3)->nullable();
            $table->json('received_weights')->nullable();

            $table->timestamps();

            $table->index(['status', 'target_warehouse_id'], 'wh_transfers_status_target_idx');
            $table->index(['status', 'shipper_id'], 'wh_transfers_status_shipper_idx');
            $table->index(['order_id', 'status'], 'wh_transfers_order_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfers');
    }
};
