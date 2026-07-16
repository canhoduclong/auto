<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('customer_card_codes');
            }

            if (!Schema::hasColumn('customers', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_pinned');
            }

            $table->index(['is_pinned', 'sort_order'], 'customers_pin_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_pin_sort_idx');

            if (Schema::hasColumn('customers', 'sort_order')) {
                $table->dropColumn('sort_order');
            }

            if (Schema::hasColumn('customers', 'is_pinned')) {
                $table->dropColumn('is_pinned');
            }
        });
    }
};
