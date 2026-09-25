<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_priorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('priority_level')->default(3);
            $table->unsignedInteger('care_score')->default(0);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('expire_date')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('takeover_eligible')->default(false);
            $table->unsignedInteger('cycle_no')->default(1);
            $table->timestamps();

            $table->index(['customer_id', 'cycle_no', 'is_active']);
            $table->index(['customer_id', 'is_active', 'priority_level']);
            $table->unique(['customer_id', 'sale_id', 'cycle_no'], 'customer_sale_cycle_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_priorities');
    }
};
