<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('supplier_products')) {
            Schema::create('supplier_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->boolean('active')->default(true);
                $table->text('note')->nullable();
                $table->timestamps();

                $table->unique(['supplier_id', 'product_id']);
                $table->index(['supplier_id', 'active']);
                $table->index(['product_id', 'active']);
            });
        }

        if (!Schema::hasTable('supplier_product_prices')) {
            Schema::create('supplier_product_prices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->date('effective_date');
                $table->decimal('material_price', 15, 2)->default(0);
                $table->decimal('processing_cost', 15, 2)->default(0);
                $table->decimal('other_cost', 15, 2)->default(0);
                $table->decimal('min_price', 15, 2)->default(0);
                $table->decimal('today_price', 15, 2)->default(0);
                $table->decimal('suggested_price', 15, 2)->default(0);
                $table->text('note')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['supplier_id', 'product_id', 'effective_date'], 'supplier_price_lookup_idx');
                $table->index(['effective_date', 'created_at']);
            });
        }

        if (Schema::hasTable('inventory_document_items') && !Schema::hasColumn('inventory_document_items', 'source_price_id')) {
            Schema::table('inventory_document_items', function (Blueprint $table) {
                $table->foreignId('source_price_id')
                    ->nullable()
                    ->after('unit_cost')
                    ->constrained('supplier_product_prices')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_document_items') && Schema::hasColumn('inventory_document_items', 'source_price_id')) {
            Schema::table('inventory_document_items', function (Blueprint $table) {
                $table->dropForeign(['source_price_id']);
                $table->dropColumn('source_price_id');
            });
        }

        Schema::dropIfExists('supplier_product_prices');
        Schema::dropIfExists('supplier_products');
    }
};
