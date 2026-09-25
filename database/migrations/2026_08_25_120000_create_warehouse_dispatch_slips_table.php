<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_dispatch_slips', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->date('business_date')->index();
            $table->foreignId('source_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('target_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('shipper_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 30)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->unsignedInteger('print_count')->default(0);
            $table->timestamps();

            $table->index(['source_warehouse_id', 'business_date'], 'dispatch_source_date_idx');
            $table->index(['target_warehouse_id', 'business_date'], 'dispatch_target_date_idx');
        });

        Schema::create('warehouse_dispatch_slip_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('warehouse_dispatch_slip_id');
            $table->foreignId('order_transfer_id')->nullable()
                ->constrained('order_transfers')->restrictOnDelete();
            $table->foreignId('inventory_transfer_id')->nullable()
                ->constrained('warehouse_inventory_transfers')->restrictOnDelete();
            $table->timestamps();

            $table->foreign('warehouse_dispatch_slip_id', 'dispatch_entry_slip_fk')
                ->references('id')->on('warehouse_dispatch_slips')->cascadeOnDelete();
            $table->unique('order_transfer_id', 'dispatch_order_transfer_unique');
            $table->unique('inventory_transfer_id', 'dispatch_inventory_transfer_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_dispatch_slip_entries');
        Schema::dropIfExists('warehouse_dispatch_slips');
    }
};
