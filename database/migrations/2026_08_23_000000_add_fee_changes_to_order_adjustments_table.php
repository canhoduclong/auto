<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_adjustments') || Schema::hasColumn('order_adjustments', 'fee_changes')) {
            return;
        }

        Schema::table('order_adjustments', function (Blueprint $table): void {
            $table->json('fee_changes')->nullable()->after('adjustment_note');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_adjustments') || ! Schema::hasColumn('order_adjustments', 'fee_changes')) {
            return;
        }

        Schema::table('order_adjustments', function (Blueprint $table): void {
            $table->dropColumn('fee_changes');
        });
    }
};
