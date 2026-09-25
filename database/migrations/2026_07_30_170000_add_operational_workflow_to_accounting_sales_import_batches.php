<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_sales_import_batches', function (Blueprint $table): void {
            $table->date('business_date')->nullable()->after('imported_by')->index();
            $table->foreignId('stock_in_document_id')->nullable()->after('business_date')
                ->constrained('inventory_documents')->nullOnDelete();
            $table->foreignId('source_warehouse_id')->nullable()->after('stock_in_document_id')
                ->constrained('warehouses')->nullOnDelete();
            $table->foreignId('target_warehouse_id')->nullable()->after('source_warehouse_id')
                ->constrained('warehouses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('accounting_sales_import_batches', function (Blueprint $table): void {
            $table->dropForeign(['stock_in_document_id']);
            $table->dropForeign(['source_warehouse_id']);
            $table->dropForeign(['target_warehouse_id']);
            $table->dropIndex(['business_date']);
            $table->dropColumn([
                'business_date',
                'stock_in_document_id',
                'source_warehouse_id',
                'target_warehouse_id',
            ]);
        });
    }
};
