<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_packing_size_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->unique(['order_item_id', 'product_variant_id'], 'packing_size_allocation_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_packing_size_allocations');
    }
};
