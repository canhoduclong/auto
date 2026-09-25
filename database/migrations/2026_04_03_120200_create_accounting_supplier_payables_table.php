<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounting_supplier_payables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->decimal('amount', 14, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue'])->default('pending');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_supplier_payables');
    }
};
