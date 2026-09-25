<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_daily_rebuilds', function (Blueprint $table): void {
            $table->id();
            $table->date('business_date')->index();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('executed_by')->constrained('users')->restrictOnDelete();
            $table->string('reason', 500);
            $table->unsignedInteger('orders_restored_count')->default(0);
            $table->unsignedInteger('inventory_syncs_reset_count')->default(0);
            $table->json('result')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_daily_rebuilds');
    }
};
