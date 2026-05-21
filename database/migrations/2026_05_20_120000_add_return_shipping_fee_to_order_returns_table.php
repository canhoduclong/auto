<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('order_returns', 'return_shipping_fee')) {
                $table->decimal('return_shipping_fee', 15, 2)
                    ->default(0)
                    ->after('refund_amount')
                    ->comment('Phi ship tra hang ve kho do manager shipper cap nhat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_returns', function (Blueprint $table) {
            if (Schema::hasColumn('order_returns', 'return_shipping_fee')) {
                $table->dropColumn('return_shipping_fee');
            }
        });
    }
};