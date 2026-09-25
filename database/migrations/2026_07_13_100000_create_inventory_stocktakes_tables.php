<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_adjustments', function (Blueprint $table): void {
            $table->decimal('quantity', 12, 3)->change();
        });

        Schema::create('inventory_stocktakes', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->nullable()->unique();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->timestamp('counted_at');
            $table->string('status', 20)->default('completed');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['warehouse_id', 'counted_at']);
        });

        Schema::create('inventory_stocktake_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stocktake_id')->constrained('inventory_stocktakes')->cascadeOnDelete();
            $table->foreignId('inventory_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('system_quantity', 12, 3);
            $table->decimal('counted_quantity', 12, 3);
            $table->decimal('difference', 12, 3);
            $table->timestamps();

            $table->index(['stocktake_id', 'difference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stocktake_items');
        Schema::dropIfExists('inventory_stocktakes');

        Schema::table('inventory_adjustments', function (Blueprint $table): void {
            $table->integer('quantity')->change();
        });
    }
};
