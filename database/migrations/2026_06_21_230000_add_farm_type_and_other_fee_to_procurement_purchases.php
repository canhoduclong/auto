<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_purchases', function (Blueprint $table): void {
            $table->string('farm_type')->nullable()->after('duck_type');
            $table->decimal('other_fee', 15, 2)->default(0)->after('processing_fee');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_purchases', function (Blueprint $table): void {
            $table->dropColumn(['farm_type', 'other_fee']);
        });
    }
};
