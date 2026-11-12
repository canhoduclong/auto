<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_purchases', function (Blueprint $table) {
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('procurement_purchases', fn (Blueprint $table) => $table->dropColumn(['vat_rate', 'vat_amount']));
    }
};
