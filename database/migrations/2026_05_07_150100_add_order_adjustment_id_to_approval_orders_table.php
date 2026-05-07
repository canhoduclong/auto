<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_orders', function (Blueprint $table) {
            $table->foreignId('order_adjustment_id')
                ->nullable()
                ->after('order_id')
                ->constrained('order_adjustments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('approval_orders', function (Blueprint $table) {
            $table->dropForeign(['order_adjustment_id']);
            $table->dropColumn('order_adjustment_id');
        });
    }
};
