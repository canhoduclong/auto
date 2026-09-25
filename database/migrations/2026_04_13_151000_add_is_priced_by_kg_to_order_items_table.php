<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'is_priced_by_kg')) {
                $table->boolean('is_priced_by_kg')->default(true)->after('unit_weight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'is_priced_by_kg')) {
                $table->dropColumn('is_priced_by_kg');
            }
        });
    }
};
