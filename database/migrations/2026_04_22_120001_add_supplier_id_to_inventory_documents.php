<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_documents', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('warehouse_id')
                ->constrained('suppliers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_documents', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });
    }
};
