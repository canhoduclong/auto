<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_documents', function (Blueprint $table): void {
            $table->foreignId('inventory_reservation_id')
                ->nullable()
                ->after('warehouse_id')
                ->constrained('inventory_reservations')
                ->nullOnDelete();
            $table->decimal('reservation_recovery_quantity', 15, 3)
                ->nullable()
                ->after('inventory_reservation_id');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('inventory_reservation_id');
            $table->dropColumn('reservation_recovery_quantity');
        });
    }
};
