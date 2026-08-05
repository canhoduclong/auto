<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_adjustment_items') || !Schema::hasColumn('order_adjustment_items', 'product_variant_id')) {
            return;
        }

        Schema::table('order_adjustment_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('product_variant_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('order_adjustment_items') || !Schema::hasColumn('order_adjustment_items', 'product_variant_id')) {
            return;
        }

        Schema::table('order_adjustment_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('product_variant_id')->nullable(false)->change();
        });
    }
};
