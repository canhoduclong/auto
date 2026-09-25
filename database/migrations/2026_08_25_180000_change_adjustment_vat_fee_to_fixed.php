<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_fee_types')) {
            return;
        }

        DB::table('order_fee_types')
            ->where('code', 'vat')
            ->update([
                'calculation_type' => 'fixed',
                'default_value' => 0,
                'description' => 'Phí VAT bổ sung nhập trực tiếp bằng số tiền.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_fee_types')) {
            return;
        }

        DB::table('order_fee_types')
            ->where('code', 'vat')
            ->update([
                'calculation_type' => 'percent',
                'default_value' => 10,
                'description' => 'Thuế VAT tính theo phần trăm tiền hàng sau chiết khấu.',
                'updated_at' => now(),
            ]);
    }
};
