<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('schedule_date');
            $table->enum('status', ['pending', 'need_review', 'approved', 'generated'])->default('pending');
            $table->enum('price_status', ['ok', 'changed'])->default('ok');
            $table->enum('stock_status', ['ok', 'insufficient'])->default('ok');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('generated_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->json('review_meta')->nullable();
            $table->timestamps();

            $table->index(['schedule_date', 'status']);
            $table->index(['customer_id', 'schedule_date']);
        });

        Schema::create('order_schedule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_schedule_id')->constrained('order_schedules')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('scheduled_price', 15, 2)->default(0);
            $table->decimal('current_price', 15, 2)->nullable();
            $table->boolean('price_diff')->default(false);
            $table->unsignedInteger('stock_available')->default(0);
            $table->boolean('stock_diff')->default(false);
            $table->timestamps();

            $table->index(['order_schedule_id']);
            $table->index(['product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_schedule_items');
        Schema::dropIfExists('order_schedules');
    }
};
