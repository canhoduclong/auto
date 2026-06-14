<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('text_order_drafts', function (Blueprint $table) {
            $table->foreignId('truck_brand_id')->nullable()->after('customer_id')->constrained('truck_brands')->nullOnDelete();
            $table->foreignId('truck_station_id')->nullable()->after('truck_brand_id')->constrained('truck_stations')->nullOnDelete();
            $table->string('truck_brand_name')->nullable()->after('address');
            $table->string('truck_station_address')->nullable()->after('truck_brand_name');
        });
    }

    public function down(): void
    {
        Schema::table('text_order_drafts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('truck_station_id');
            $table->dropConstrainedForeignId('truck_brand_id');
            $table->dropColumn(['truck_brand_name', 'truck_station_address']);
        });
    }
};
