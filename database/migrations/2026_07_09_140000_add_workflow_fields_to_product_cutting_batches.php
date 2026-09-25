<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_cutting_batches', function (Blueprint $table): void {
            if (!Schema::hasColumn('product_cutting_batches', 'order_id')) {
                $table->foreignId('order_id')->nullable()->after('target_product_variant_id')->constrained('orders')->nullOnDelete();
            }
            if (!Schema::hasColumn('product_cutting_batches', 'status')) {
                $table->string('status', 20)->default('completed')->after('order_id');
            }
            if (!Schema::hasColumn('product_cutting_batches', 'source_materials')) {
                $table->json('source_materials')->nullable()->after('status');
            }
            if (!Schema::hasColumn('product_cutting_batches', 'completed_by')) {
                $table->foreignId('completed_by')->nullable()->after('performed_by')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('product_cutting_batches', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('completed_by');
            }

            $table->index(['order_id', 'status'], 'product_cutting_batches_order_status');
        });
    }

    public function down(): void
    {
        Schema::table('product_cutting_batches', function (Blueprint $table): void {
            if (Schema::hasColumn('product_cutting_batches', 'order_id')) {
                $table->dropForeign(['order_id']);
            }
            if (Schema::hasColumn('product_cutting_batches', 'completed_by')) {
                $table->dropForeign(['completed_by']);
            }
            $table->dropIndex('product_cutting_batches_order_status');
            foreach (['completed_at', 'completed_by', 'source_materials', 'status', 'order_id'] as $column) {
                if (Schema::hasColumn('product_cutting_batches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
