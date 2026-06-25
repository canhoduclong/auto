<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'external_account_number')) {
                $table->string('external_account_number', 100)->nullable()->after('external_recipient');
            }
            if (!Schema::hasColumn('transactions', 'external_bank_name')) {
                $table->string('external_bank_name', 150)->nullable()->after('external_account_number');
            }
            if (!Schema::hasColumn('transactions', 'external_bank_branch')) {
                $table->string('external_bank_branch', 150)->nullable()->after('external_bank_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            foreach (['external_bank_branch', 'external_bank_name', 'external_account_number'] as $column) {
                if (Schema::hasColumn('transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
