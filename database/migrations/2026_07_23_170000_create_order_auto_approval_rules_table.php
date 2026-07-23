<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_auto_approval_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('order_type', 32);
            $table->boolean('enabled')->default(false);
            $table->boolean('require_min_price')->default(true);
            $table->boolean('allow_bulk_below_min')->default(false);
            $table->unsignedInteger('bulk_min_quantity')->default(100);
            $table->decimal('bulk_below_min_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'order_type']);
            $table->index(['order_type', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_auto_approval_rules');
    }
};
