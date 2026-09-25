<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_balance_refresh_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refreshed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('filter_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->unsignedInteger('accounts_reconciled')->default(0);
            $table->unsignedInteger('accounts_updated')->default(0);
            $table->decimal('total_amount_adjusted', 18, 2)->default(0);
            $table->json('results_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_balance_refresh_logs');
    }
};
