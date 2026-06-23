<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('procurement_purchases')->where('payment_status', 'paid')->update([
            'paid_amount' => DB::raw('total_amount'),
            'remaining_amount' => 0,
        ]);
    }

    public function down(): void
    {
    }
};
