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
        Schema::table('order_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('order_returns', 'order_adjustment_id')) {
                $table->foreignId('order_adjustment_id')->nullable()->after('order_id')->constrained('order_adjustments')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_returns', function (Blueprint $table) {
            if (Schema::hasColumn('order_returns', 'order_adjustment_id')) {
                $table->dropConstrainedForeignId('order_adjustment_id');
            }
        });
    }
};
