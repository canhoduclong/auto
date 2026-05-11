<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['deposit', 'withdraw'])->comment('deposit = nạp tiền, withdraw = rút tiền');
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2)->comment('Số dư trước khi điều chỉnh');
            $table->decimal('balance_after', 15, 2)->comment('Số dư sau khi điều chỉnh');
            $table->string('note', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_adjustments');
    }
};
