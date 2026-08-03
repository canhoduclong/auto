<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_purchases', function (Blueprint $table): void {
            $table->string('entry_mode', 30)->default('duck_batch')->after('purchase_type')->index();
            $table->foreignId('inventory_document_id')->nullable()->after('warehouse_id')->constrained('inventory_documents')->nullOnDelete();
        });

        Schema::create('procurement_purchase_product_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('procurement_purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_price_id')->nullable()->constrained('supplier_product_prices')->nullOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->decimal('weight', 12, 3)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->decimal('received_quantity', 12, 3)->nullable();
            $table->decimal('received_weight', 12, 3)->nullable();
            $table->string('condition')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->unique(['procurement_purchase_id', 'product_variant_id'], 'proc_purchase_variant_unique');
        });

        Schema::create('procurement_purchase_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->timestamps();
        });

        Schema::create('procurement_purchase_template_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('procurement_purchase_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('weight', 12, 3)->default(0);
            $table->timestamps();
            $table->unique(['procurement_purchase_template_id', 'product_variant_id'], 'proc_template_variant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_purchase_template_items');
        Schema::dropIfExists('procurement_purchase_templates');
        Schema::dropIfExists('procurement_purchase_product_items');
        Schema::table('procurement_purchases', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('inventory_document_id');
            $table->dropColumn('entry_mode');
        });
    }
};
