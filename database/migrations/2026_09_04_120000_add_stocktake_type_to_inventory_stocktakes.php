<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_stocktakes', function (Blueprint $table): void {
            $table->string('stocktake_type', 20)->default('opening')->after('counted_at');
            $table->index(['warehouse_id', 'stocktake_type', 'counted_at'], 'stocktakes_warehouse_type_counted_index');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_stocktakes', function (Blueprint $table): void {
            $table->dropIndex('stocktakes_warehouse_type_counted_index');
            $table->dropColumn('stocktake_type');
        });
    }
};
