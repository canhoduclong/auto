<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('accounting_adjusted_weight', 12, 3)->nullable();
        });
        Schema::table('order_adjustments', function (Blueprint $table) {
            $table->unsignedBigInteger('weight_confirmed_by')->nullable();
            $table->timestamp('weight_confirmed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('accounting_adjusted_weight'));
        Schema::table('order_adjustments', fn (Blueprint $table) => $table->dropColumn(['weight_confirmed_by', 'weight_confirmed_at']));
    }
};
