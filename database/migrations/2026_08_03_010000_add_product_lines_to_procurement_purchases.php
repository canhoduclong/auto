<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chỉ thêm cột nếu chưa tồn tại
        Schema::table('procurement_purchases', function (Blueprint $table): void {

            if (!Schema::hasColumn('procurement_purchases', 'entry_mode')) {
                $table->string('entry_mode', 30)
                    ->default('duck_batch')
                    ->after('purchase_type')
                    ->index();
            }

            if (!Schema::hasColumn('procurement_purchases', 'inventory_document_id')) {
                $table->foreignId('inventory_document_id')
                    ->nullable()
                    ->after('warehouse_id')
                    ->constrained('inventory_documents')
                    ->nullOnDelete();
            }
        });

        if (!Schema::hasTable('procurement_purchase_product_items')) {
            Schema::create('procurement_purchase_product_items', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('procurement_purchase_id');
                $table->unsignedBigInteger('product_variant_id');
                $table->unsignedBigInteger('source_price_id')->nullable();
                $table->decimal('quantity', 12, 3);
                $table->decimal('weight', 12, 3)->default(0);
                $table->decimal('unit_cost', 15, 2)->default(0);
                $table->decimal('line_total', 15, 2)->default(0);
                $table->decimal('received_quantity', 12, 3)->nullable();
                $table->decimal('received_weight', 12, 3)->nullable();
                $table->string('condition')->nullable();
                $table->string('note', 500)->nullable();
                $table->timestamps();

                $table->foreign('procurement_purchase_id', 'proc_product_item_purchase_fk')
                    ->references('id')
                    ->on('procurement_purchases')
                    ->cascadeOnDelete();

                $table->foreign('product_variant_id', 'proc_product_item_variant_fk')
                    ->references('id')
                    ->on('product_variants')
                    ->restrictOnDelete();

                $table->foreign('source_price_id', 'proc_product_item_price_fk')
                    ->references('id')
                    ->on('supplier_product_prices')
                    ->nullOnDelete();

                $table->unique(
                    ['procurement_purchase_id', 'product_variant_id'],
                    'proc_purchase_variant_unique'
                );
            });
        }

        if (!Schema::hasTable('procurement_purchase_templates')) {
            Schema::create('procurement_purchase_templates', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name', 150);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('procurement_purchase_template_items')) {
            Schema::create('procurement_purchase_template_items', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('procurement_purchase_template_id');
                $table->unsignedBigInteger('product_variant_id');
                $table->decimal('quantity', 12, 3)->default(1);
                $table->decimal('weight', 12, 3)->default(0);
                $table->timestamps();

                $table->foreign('procurement_purchase_template_id', 'proc_template_item_template_fk')
                    ->references('id')
                    ->on('procurement_purchase_templates')
                    ->cascadeOnDelete();

                $table->foreign('product_variant_id', 'proc_template_item_variant_fk')
                    ->references('id')
                    ->on('product_variants')
                    ->cascadeOnDelete();

                $table->unique(
                    ['procurement_purchase_template_id', 'product_variant_id'],
                    'proc_template_variant_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_purchase_template_items');
        Schema::dropIfExists('procurement_purchase_templates');
        Schema::dropIfExists('procurement_purchase_product_items');

        Schema::table('procurement_purchases', function (Blueprint $table): void {
            if (Schema::hasColumn('procurement_purchases', 'inventory_document_id')) {
                $table->dropConstrainedForeignId('inventory_document_id');
            }

            if (Schema::hasColumn('procurement_purchases', 'entry_mode')) {
                $table->dropColumn('entry_mode');
            }
        });
    }
};