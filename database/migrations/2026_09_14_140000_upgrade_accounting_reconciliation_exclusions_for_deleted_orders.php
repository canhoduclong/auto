<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounting_reconciliation_exclusions')) {
            return;
        }

        if (! Schema::hasColumn('accounting_reconciliation_exclusions', 'deleted_order_id')) {
            Schema::table('accounting_reconciliation_exclusions', function (Blueprint $table): void {
                $table->unsignedBigInteger('deleted_order_id')->nullable()->unique()->after('order_id');
            });
        }

        Schema::table('accounting_reconciliation_exclusions', function (Blueprint $table): void {
            $table->unsignedBigInteger('order_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('accounting_reconciliation_exclusions')
            || ! Schema::hasColumn('accounting_reconciliation_exclusions', 'deleted_order_id')) {
            return;
        }

        Schema::table('accounting_reconciliation_exclusions', function (Blueprint $table): void {
            $table->dropUnique(['deleted_order_id']);
            $table->dropColumn('deleted_order_id');
        });
    }
};
