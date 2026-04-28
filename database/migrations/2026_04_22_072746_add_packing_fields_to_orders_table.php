<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Số thứ tự đơn trong ngày (ưu tiên ráp hàng của sale)
            $table->unsignedSmallInteger('daily_sequence')->nullable()->after('code');
            // Trạng thái tồn kho: 1 = đủ hàng, 0 = thiếu hàng, null = chưa kiểm tra
            $table->tinyInteger('stock_sufficient')->nullable()->after('daily_sequence');
            // Chi tiết thiếu hàng (JSON), null khi đủ hàng
            $table->text('stock_shortage_detail')->nullable()->after('stock_sufficient');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['daily_sequence', 'stock_sufficient', 'stock_shortage_detail']);
        });
    }
};
