<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_stocktake_items', function (Blueprint $table): void {
            $table->decimal('physical_counted_quantity', 15, 3)->nullable()->after('counted_quantity');
            $table->decimal('packed_reserved_quantity', 15, 3)->default(0)->after('physical_counted_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_stocktake_items', function (Blueprint $table): void {
            $table->dropColumn(['physical_counted_quantity', 'packed_reserved_quantity']);
        });
    }
};
