<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_order_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('approval_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('start_date');
            $table->date('last_processed_date')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'start_date']);
            $table->index(['created_by', 'customer_id']);
        });

        Schema::create('daily_order_schedule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_order_schedule_id')->constrained('daily_order_schedules')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('scheduled_price', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['daily_order_schedule_id']);
            $table->index(['product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_order_schedule_items');
        Schema::dropIfExists('daily_order_schedules');
    }
};