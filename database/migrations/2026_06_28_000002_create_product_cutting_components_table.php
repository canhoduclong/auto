<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_cutting_components')) {
            Schema::create('product_cutting_components', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('component_product_variant_id')->constrained('product_variants')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['product_id', 'component_product_variant_id'], 'product_cutting_components_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_cutting_components');
    }
};
