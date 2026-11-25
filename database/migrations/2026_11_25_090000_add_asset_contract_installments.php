<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_contract_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('asset_contracts')->restrictOnDelete();
            $table->string('kind', 24);
            $table->string('label', 150);
            $table->unsignedBigInteger('amount');
            $table->date('due_on')->nullable();
            $table->string('milestone', 500)->nullable();
            $table->timestamps();
        });
        Schema::table('asset_contract_payments', function (Blueprint $table) {
            $table->foreignId('installment_id')->nullable()->constrained('asset_contract_installments')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('asset_contract_payments', fn (Blueprint $table) => $table->dropConstrainedForeignId('installment_id'));
        Schema::dropIfExists('asset_contract_installments');
    }
};
