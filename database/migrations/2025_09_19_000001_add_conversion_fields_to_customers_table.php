<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('delivery_time')->nullable()->after('note');
            $table->boolean('foam_box_required')->default(false)->after('delivery_time');
            $table->integer('foam_box_price')->nullable()->after('foam_box_required');
            $table->boolean('use_truck_station')->default(false)->after('foam_box_price');
            $table->string('truck_station_address')->nullable()->after('use_truck_station');
            $table->string('truck_receive_time')->nullable()->after('truck_station_address');
            $table->string('truck_return_time')->nullable()->after('truck_receive_time');
            $table->string('truck_return_address')->nullable()->after('truck_return_time');
            $table->string('truck_invoice_image')->nullable()->after('truck_return_address');
            $table->string('truck_delivery_image')->nullable()->after('truck_invoice_image');
            $table->string('truck_station_phone')->nullable()->after('truck_delivery_image');
            $table->integer('truck_fee')->nullable()->after('truck_station_phone');
        });
    }
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_time',
                'foam_box_required',
                'foam_box_price',
                'use_truck_station',
                'truck_station_address',
                'truck_receive_time',
                'truck_return_time',
                'truck_return_address',
                'truck_invoice_image',
                'truck_delivery_image',
                'truck_station_phone',
                'truck_fee',
            ]);
        });
    }
};
