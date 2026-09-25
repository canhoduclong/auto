<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_sales_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_hash', 64)->unique();
            $table->unsignedInteger('row_count')->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->longText('raw_text');
            $table->timestamps();
        });

        Schema::create('accounting_sales_entries', function (Blueprint $table): void {
            $table->id();
            $table->date('entry_date')->index();
            $table->unsignedTinyInteger('entry_month')->index();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_code', 50)->nullable()->index();
            $table->string('customer_name');
            $table->foreignId('sale_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('sale_name')->nullable();
            $table->string('unit', 100);
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('unit_weight', 12, 3)->default(1);
            $table->decimal('total_quantity', 15, 3)->default(0);
            $table->decimal('unit_price', 18, 2)->nullable();
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->string('source', 20)->index();
            $table->string('source_key')->nullable()->unique();
            $table->foreignId('order_id')->nullable()->index()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('accounting_reconciliation_id')->nullable()->constrained('accounting_reconciliations')->cascadeOnDelete();
            $table->foreignId('import_batch_id')->nullable()->index()->constrained('accounting_sales_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('import_row')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['source', 'entry_date']);
            $table->index(['sale_id', 'entry_date']);
            $table->index(['customer_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_sales_entries');
        Schema::dropIfExists('accounting_sales_import_batches');
    }
};
