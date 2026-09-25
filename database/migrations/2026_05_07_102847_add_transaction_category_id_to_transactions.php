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
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('transaction_category_id')->nullable()->after('expense_type_id');
            $table->unsignedBigInteger('account_id')->nullable()->after('transaction_category_id');
            $table->foreign('transaction_category_id')->references('id')->on('transaction_categories')->nullOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['transaction_category_id']);
            $table->dropForeign(['account_id']);
            $table->dropColumn(['transaction_category_id', 'account_id']);
        });
    }
};
