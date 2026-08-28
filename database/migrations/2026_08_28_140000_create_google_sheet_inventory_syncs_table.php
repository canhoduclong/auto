<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_sheet_inventory_syncs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('spreadsheet_id', 100);
            $table->unsignedBigInteger('sheet_id');
            $table->date('inventory_date');
            $table->unsignedInteger('sync_number')->default(1);
            $table->foreignId('import_document_id')->nullable()->constrained('inventory_documents')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('completed');
            $table->decimal('total_positive_delta', 12, 3)->default(0);
            $table->decimal('total_negative_delta', 12, 3)->default(0);
            $table->unsignedInteger('applied_rows_count')->default(0);
            $table->json('snapshot');
            $table->json('changes')->nullable();
            $table->timestamps();

            $table->index(
                ['warehouse_id', 'spreadsheet_id', 'sheet_id', 'inventory_date'],
                'sheet_inventory_sync_source_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_sheet_inventory_syncs');
    }
};
