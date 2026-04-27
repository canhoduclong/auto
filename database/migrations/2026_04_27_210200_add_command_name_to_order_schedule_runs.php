<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_schedule_runs', function (Blueprint $table) {
            $table->string('command_name', 120)
                ->default('order-schedules:evaluate-today')
                ->after('trigger_type');

            $table->index(['command_name', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('order_schedule_runs', function (Blueprint $table) {
            $table->dropIndex(['command_name', 'created_at']);
            $table->dropColumn('command_name');
        });
    }
};