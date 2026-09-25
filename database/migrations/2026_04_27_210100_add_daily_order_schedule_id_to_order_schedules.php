<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_schedules', function (Blueprint $table) {
            $table->foreignId('daily_order_schedule_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('daily_order_schedules')
                ->nullOnDelete();

            $table->index(['daily_order_schedule_id', 'schedule_date'], 'os_daily_schedule_date_idx');
        });
    }

    public function down(): void
    {
        
        Schema::table('order_schedules', function (Blueprint $table) {
            // 1. Drop foreign key trước
            $table->dropForeign(['daily_order_schedule_id']); 

            // 2. Sau đó mới drop index
            $table->dropIndex('os_daily_schedule_date_idx');

            //$table->dropIndex('os_daily_schedule_date_idx');
            //$table->dropConstrainedForeignId('daily_order_schedule_id');
        });
    }
};