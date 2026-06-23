<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_purchases', function (Blueprint $table): void {
            $table->decimal('paid_amount', 15, 2)->default(0)->after('total_amount');
            $table->decimal('remaining_amount', 15, 2)->default(0)->after('paid_amount');
            $table->date('payment_due_date')->nullable()->after('remaining_amount');
        });
        DB::table('procurement_purchases')->where('payment_status', '!=', 'paid')->update([
            'remaining_amount' => DB::raw('total_amount'),
        ]);
        DB::table('procurement_purchases')->where('payment_status', 'paid')->update([
            'paid_amount' => DB::raw('total_amount'),
            'remaining_amount' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('procurement_purchases', function (Blueprint $table): void {
            $table->dropColumn(['paid_amount', 'remaining_amount', 'payment_due_date']);
        });
    }
};
