<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds weight re-entry and weight loss tracking to return items for warehouse processing.
     */
    public function up(): void
    {
        Schema::table('return_items', function (Blueprint $table) {
            // Original weight from order item (kg)
            $table->decimal('original_weight', 12, 3)->nullable()->after('condition')->comment('Khối lượng gốc từ đơn hàng');
            
            // Re-weighed weight by warehouse (kg)
            $table->decimal('received_weight', 12, 3)->nullable()->after('original_weight')->comment('Khối lượng được cân lại tại kho');
            
            // Calculated weight loss (kg)
            $table->decimal('weight_loss', 12, 3)->nullable()->after('received_weight')->comment('Hao hụt khối lượng sản phẩm');
            
            // Timestamp for weight entry
            $table->timestamp('weight_confirmed_at')->nullable()->after('weight_loss')->comment('Thời gian xác nhận cân nặng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('return_items', function (Blueprint $table) {
            $table->dropColumn(['original_weight', 'received_weight', 'weight_loss', 'weight_confirmed_at']);
        });
    }
};
