<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_sheet_inventory_exports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('spreadsheet_id', 100);
            $table->unsignedBigInteger('sheet_id');
            $table->date('inventory_date');
            $table->string('sheet_name');
            $table->unsignedInteger('written_rows_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['warehouse_id', 'inventory_date'], 'sheet_inventory_exports_warehouse_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_sheet_inventory_exports');
    }
};
