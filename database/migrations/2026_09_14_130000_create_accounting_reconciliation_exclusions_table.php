<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_reconciliation_exclusions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('deleted_order_id')->nullable()->unique();
            $table->foreignId('excluded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->timestamp('excluded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_reconciliation_exclusions');
    }
};
