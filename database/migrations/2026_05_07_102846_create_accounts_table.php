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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['cash', 'bank'])->default('cash');
            $table->string('account_number', 50)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->decimal('balance', 18, 2)->default(0);
            $table->decimal('warning_threshold', 18, 2)->default(50000000);
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
