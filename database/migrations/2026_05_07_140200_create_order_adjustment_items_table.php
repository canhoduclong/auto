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
        Schema::create('order_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('original_quantity')->default(0);
            $table->integer('adjusted_quantity')->default(0);
            $table->decimal('original_price', 12, 2)->default(0);
            $table->decimal('adjusted_price', 12, 2)->default(0);
            $table->decimal('original_weight', 12, 3)->nullable();
            $table->decimal('adjusted_weight', 12, 3)->nullable();
            $table->integer('warehouse_received_quantity')->nullable();
            $table->decimal('warehouse_received_weight', 12, 3)->nullable();
            $table->string('warehouse_condition', 255)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_adjustment_items');
    }
};
