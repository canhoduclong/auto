<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // NULL marks legacy orders, which continue to use the customer's current setting.
            // New order flows always store an explicit true/false snapshot.
            $table->boolean('use_truck_station')->nullable()->after('delivery_time');
            $table->foreignId('truck_station_id')->nullable()->after('use_truck_station')
                ->constrained('truck_stations')->nullOnDelete();
            $table->string('truck_station_name')->nullable()->after('truck_station_id');
            $table->string('truck_station_address')->nullable()->after('truck_station_name');
            $table->string('truck_station_phone', 30)->nullable()->after('truck_station_address');
            $table->string('truck_receive_time')->nullable()->after('truck_station_phone');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('truck_station_id');
            $table->dropColumn([
                'use_truck_station',
                'truck_station_name',
                'truck_station_address',
                'truck_station_phone',
                'truck_receive_time',
            ]);
        });
    }
};
