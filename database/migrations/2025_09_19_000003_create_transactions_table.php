<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('type', 30); // payment, refund, fee, extra_income, extra_expense
            $table->string('method', 50)->nullable(); // cod, bank, etc
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
