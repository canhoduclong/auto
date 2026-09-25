<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'current_owner_sale_id')) {
                $table->foreignId('current_owner_sale_id')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('customers', 'customer_status')) {
                $table->string('customer_status', 32)->default('active')->after('status');
            }
            if (!Schema::hasColumn('customers', 'free_from_date')) {
                $table->timestamp('free_from_date')->nullable()->after('customer_status');
            }
            if (!Schema::hasColumn('customers', 'current_cycle_no')) {
                $table->unsignedInteger('current_cycle_no')->default(1)->after('free_from_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'current_owner_sale_id')) {
                $table->dropConstrainedForeignId('current_owner_sale_id');
            }
            if (Schema::hasColumn('customers', 'current_cycle_no')) {
                $table->dropColumn('current_cycle_no');
            }
            if (Schema::hasColumn('customers', 'free_from_date')) {
                $table->dropColumn('free_from_date');
            }
            if (Schema::hasColumn('customers', 'customer_status')) {
                $table->dropColumn('customer_status');
            }
        });
    }
};
