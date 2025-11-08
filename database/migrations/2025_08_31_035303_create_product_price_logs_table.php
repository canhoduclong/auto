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
        Schema::create('product_price_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedBigInteger('price_rule_id')->nullable(); 
            $table->decimal('old_price', 10, 2)->nullable();
            $table->decimal('new_price', 10, 2);
            $table->timestamp('applied_at')->useCurrent(); // thời điểm áp dụng
            $table->unsignedBigInteger('applied_by')->nullable(); // User/system
            $table->timestamps();

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Khóa ngoại
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('cascade');
            $table->foreign('price_rule_id')->references('id')->on('product_price_rules')->onDelete('set null');
            $table->foreign('applied_by')->references('id')->on('users')->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_price_logs');
    }
};
