<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_purchases', function (Blueprint $table): void {
            $table->decimal('procurement_fee', 15, 2)->default(0)->after('processing_fee');
            $table->decimal('transportation_fee', 15, 2)->default(0)->after('procurement_fee');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_purchases', function (Blueprint $table): void {
            $table->dropColumn(['procurement_fee', 'transportation_fee']);
        });
    }
};
