<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_shipping_fee_histories')) {
            Schema::create('customer_shipping_fee_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('old_fee', 12, 2)->nullable();
                $table->decimal('new_fee', 12, 2)->nullable();
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('note', 500)->nullable();
                $table->timestamp('changed_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_shipping_fee_histories');
    }
};