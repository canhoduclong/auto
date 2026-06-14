<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_document_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->index(['warehouse_id', 'supplier_id']);
        });

        Schema::create('inventory_document_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_document_template_id');
            $table->foreignId('product_variant_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->foreign('inventory_document_template_id', 'stock_in_tpl_item_tpl_fk')
                ->references('id')
                ->on('inventory_document_templates')
                ->cascadeOnDelete();
            $table->foreign('product_variant_id', 'stock_in_tpl_item_variant_fk')
                ->references('id')
                ->on('product_variants')
                ->cascadeOnDelete();
            $table->unique(
                ['inventory_document_template_id', 'product_variant_id'],
                'inventory_doc_template_variant_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_document_template_items');
        Schema::dropIfExists('inventory_document_templates');
    }
};
