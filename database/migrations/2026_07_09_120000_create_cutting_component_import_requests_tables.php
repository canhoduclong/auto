<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cutting_component_import_requests')) {
            Schema::create('cutting_component_import_requests', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
                $table->date('request_date');
                $table->string('status', 20)->default('open');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('received_at')->nullable();
                $table->foreignId('inventory_document_id')->nullable()->constrained('inventory_documents')->nullOnDelete();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index(['warehouse_id', 'request_date', 'status'], 'cut_comp_import_req_lookup');
            });
        }

        if (!Schema::hasTable('cutting_component_import_request_items')) {
            Schema::create('cutting_component_import_request_items', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('cutting_component_import_request_id');
                $table->foreignId('cutting_batch_id')->nullable()->constrained('product_cutting_batches')->nullOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->foreignId('product_variant_id');
                $table->decimal('quantity', 12, 3)->default(0);
                $table->string('source_order_code')->nullable();
                $table->timestamps();

                $table->index(['product_variant_id'], 'cut_comp_import_req_item_variant');
                $table->foreign('product_variant_id', 'cut_comp_import_req_item_variant_fk')
                    ->references('id')
                    ->on('product_variants')
                    ->cascadeOnDelete();
                $table->foreign('cutting_component_import_request_id', 'cut_comp_import_req_item_req_fk')
                    ->references('id')
                    ->on('cutting_component_import_requests')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cutting_component_import_request_items');
        Schema::dropIfExists('cutting_component_import_requests');
    }
};
