<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_cutting_batches', function (Blueprint $table): void {
            if (!Schema::hasColumn('product_cutting_batches', 'input_weight')) {
                $table->decimal('input_weight', 12, 3)->default(0)->after('component_import_document_id');
            }
            if (!Schema::hasColumn('product_cutting_batches', 'actual_component_weight')) {
                $table->decimal('actual_component_weight', 12, 3)->default(0)->after('actual_finished_weight');
            }
            if (!Schema::hasColumn('product_cutting_batches', 'loss_weight')) {
                $table->decimal('loss_weight', 12, 3)->default(0)->after('actual_component_weight');
            }
            if (!Schema::hasColumn('product_cutting_batches', 'loss_percent')) {
                $table->decimal('loss_percent', 7, 3)->default(0)->after('loss_weight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_cutting_batches', function (Blueprint $table): void {
            foreach (['loss_percent', 'loss_weight', 'actual_component_weight', 'input_weight'] as $column) {
                if (Schema::hasColumn('product_cutting_batches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
