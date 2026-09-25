<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (!Schema::hasColumn('products', 'product_type')) {
                $table->string('product_type', 20)->default('whole')->after('unit');
                $table->index('product_type');
            }
        });

        if (!Schema::hasTable('product_component_ratios')) {
            Schema::create('product_component_ratios', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('source_product_variant_id')->constrained('product_variants')->cascadeOnDelete();
                $table->foreignId('component_product_variant_id')->constrained('product_variants')->cascadeOnDelete();
                $table->decimal('standard_weight', 12, 3)->default(0);
                $table->decimal('percentage', 7, 3)->default(0);
                $table->timestamps();

                $table->unique(['source_product_variant_id', 'component_product_variant_id'], 'component_ratio_unique');
            });
        }

        if (!Schema::hasTable('product_cutting_batches')) {
            Schema::create('product_cutting_batches', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
                $table->foreignId('target_product_variant_id')->constrained('product_variants')->cascadeOnDelete();
                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('export_document_id')->nullable()->constrained('inventory_documents')->nullOnDelete();
                $table->foreignId('finished_import_document_id')->nullable()->constrained('inventory_documents')->nullOnDelete();
                $table->foreignId('component_import_document_id')->nullable()->constrained('inventory_documents')->nullOnDelete();
                $table->decimal('planned_finished_weight', 12, 3)->default(0);
                $table->decimal('actual_finished_weight', 12, 3)->default(0);
                $table->json('planned_components')->nullable();
                $table->json('actual_components')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        foreach ([
            ['inventories', 'quantity'],
            ['inventories', 'reserved_quantity'],
            ['inventories', 'low_stock_threshold'],
            ['inventory_document_items', 'quantity'],
            ['inventory_movements', 'quantity'],
        ] as [$table, $column]) {
            if (Schema::hasColumn($table, $column)) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` DECIMAL(12,3) NOT NULL DEFAULT 0");
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_cutting_batches');
        Schema::dropIfExists('product_component_ratios');

        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'product_type')) {
                $table->dropIndex(['product_type']);
                $table->dropColumn('product_type');
            }
        });
    }
};
