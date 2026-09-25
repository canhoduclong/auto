<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->decimal('company_price_at_order', 15, 2)->nullable()->after('base_price');
        });

        DB::table('order_items')->whereNull('company_price_at_order')->update([
            'company_price_at_order' => DB::raw('COALESCE(base_price, price)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn('company_price_at_order');
        });
    }
};
