<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('customers', 'debt_type')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('debt_type', 32)->default('normal')->after('customer_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'debt_type')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('debt_type');
            });
        }
    }
};
