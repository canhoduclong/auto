<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('text_order_drafts', function (Blueprint $table) {
            $table->text('truck_station_address')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('text_order_drafts', function (Blueprint $table) {
            $table->string('truck_station_address')->nullable()->change();
        });
    }
};
