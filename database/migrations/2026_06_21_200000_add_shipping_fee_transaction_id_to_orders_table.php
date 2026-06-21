<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shipping_fee_transaction_id')
                ->nullable()
                ->after('shipping_fee')
                ->constrained('transactions')
                ->nullOnDelete();
        });

        // Preserve any requests created by the previous automatic flow.
        DB::table('transactions')
            ->where('request_source', 'shipper')
            ->whereNotNull('order_id')
            ->where('request_title', 'like', 'Chi phí ship đơn #%')
            ->orderBy('id')
            ->each(function ($transaction): void {
                DB::table('orders')
                    ->where('id', $transaction->order_id)
                    ->whereNull('shipping_fee_transaction_id')
                    ->update(['shipping_fee_transaction_id' => $transaction->id]);
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_fee_transaction_id');
        });
    }
};
