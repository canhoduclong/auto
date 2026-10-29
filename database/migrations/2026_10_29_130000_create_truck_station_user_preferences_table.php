<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('truck_station_user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('truck_station_id')->constrained('truck_stations')->cascadeOnDelete();
            $table->boolean('is_pinned')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'truck_station_id'], 'truck_station_user_pref_unique');
            $table->index(['user_id', 'is_pinned', 'sort_order'], 'truck_station_user_pref_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('truck_station_user_preferences');
    }
};
