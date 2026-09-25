<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'truck_route_id')) {
                $table->foreignId('truck_route_id')
                    ->nullable()
                    ->after('truck_station_id')
                    ->constrained('truck_routes')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'truck_route_id')) {
                $table->dropConstrainedForeignId('truck_route_id');
            }
        });
    }
};
