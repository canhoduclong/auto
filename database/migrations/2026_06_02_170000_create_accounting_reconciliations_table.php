<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accounting_reconciliations')) {
            return;
        }

        Schema::create('accounting_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shipper_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('shipping_fee', 15, 2)->default(0);
            $table->decimal('return_amount', 15, 2)->default(0);
            $table->decimal('recognized_revenue', 15, 2)->default(0);
            $table->string('status')->default('pending');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['status', 'confirmed_at']);
            $table->index(['sale_id', 'confirmed_at']);
            $table->index(['shipper_id', 'confirmed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_reconciliations');
    }
};
