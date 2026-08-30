<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_sheets_order_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->date('business_date');
            $table->string('date_field', 32)->default('business_date');
            $table->foreignId('synced_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('synced_activity_at')->nullable();
            $table->timestamp('synced_at');
            $table->unsignedInteger('order_count')->default(0);
            $table->unsignedInteger('detail_count')->default(0);
            $table->unsignedInteger('deleted_count')->default(0);
            $table->timestamps();
            $table->unique(['business_date', 'date_field'], 'order_sheet_sync_runs_day_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_sheets_order_sync_runs');
    }
};
