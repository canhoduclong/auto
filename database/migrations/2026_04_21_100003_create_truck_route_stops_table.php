<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('truck_route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('truck_route_id')
                ->constrained('truck_routes')->cascadeOnDelete();
            $table->foreignId('truck_station_id')
                ->constrained('truck_stations')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('Thứ tự chặng');
            $table->string('arrival_time', 10)->nullable()->comment('Giờ đến/đi VD: 05:00');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['truck_route_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('truck_route_stops');
    }
};
