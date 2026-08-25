<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_dispatch_slip_entries', function (Blueprint $table): void {
            $table->json('snapshot')->nullable()->after('inventory_transfer_id');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_dispatch_slip_entries', function (Blueprint $table): void {
            $table->dropColumn('snapshot');
        });
    }
};
