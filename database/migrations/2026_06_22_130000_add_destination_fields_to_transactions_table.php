<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('destination_type', 20)->nullable()->after('account_id');
            $table->foreignId('destination_account_id')->nullable()->after('destination_type')
                ->constrained('accounts')->nullOnDelete();
            $table->string('external_recipient')->nullable()->after('destination_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['destination_account_id']);
            $table->dropColumn(['destination_type', 'destination_account_id', 'external_recipient']);
        });
    }
};
