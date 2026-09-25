<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->string('flow_direction', 10)->default('out')->after('name');
        });

        DB::table('transaction_categories')
            ->whereIn('code', ['TTBH', 'TTM'])
            ->update(['flow_direction' => 'in']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->dropColumn('flow_direction');
        });
    }
};
