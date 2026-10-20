<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_returns', function (Blueprint $table) {
            $table->foreignId('assigned_shipper_id')->nullable()->constrained('users');
            $table->foreignId('accounting_confirmed_by')->nullable()->constrained('users');
            $table->timestamp('accounting_confirmed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_returns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_shipper_id');
            $table->dropConstrainedForeignId('accounting_confirmed_by');
            $table->dropColumn('accounting_confirmed_at');
        });
    }
};
