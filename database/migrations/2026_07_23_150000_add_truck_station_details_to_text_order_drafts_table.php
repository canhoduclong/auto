<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('text_order_drafts', function (Blueprint $table) {
            $table->boolean('use_truck_station')->default(false)->after('delivery_time');
            $table->string('truck_station_name')->nullable()->after('truck_brand_name');
            $table->string('truck_station_phone', 30)->nullable()->after('truck_station_address');
            $table->string('truck_receive_time')->nullable()->after('truck_station_phone');
        });
    }

    public function down(): void
    {
        Schema::table('text_order_drafts', function (Blueprint $table) {
            $table->dropColumn([
                'use_truck_station',
                'truck_station_name',
                'truck_station_phone',
                'truck_receive_time',
            ]);
        });
    }
};
