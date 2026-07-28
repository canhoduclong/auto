<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('accounting_sales_import_batch_id')->nullable()->index()
                ->constrained('accounting_sales_import_batches')->nullOnDelete();
            $table->string('imported_sales_group_key', 64)->nullable()->unique();
            $table->boolean('needs_operational_completion')->default(false)->index();
            $table->text('operational_completion_note')->nullable();
            $table->foreignId('operational_completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('operational_completed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['accounting_sales_import_batch_id']);
            $table->dropForeign(['operational_completed_by']);
            $table->dropUnique(['imported_sales_group_key']);
            $table->dropIndex(['accounting_sales_import_batch_id']);
            $table->dropIndex(['needs_operational_completion']);
            $table->dropColumn([
                'accounting_sales_import_batch_id', 'imported_sales_group_key',
                'needs_operational_completion', 'operational_completion_note',
                'operational_completed_by', 'operational_completed_at',
            ]);
        });
    }
};
