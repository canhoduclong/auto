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
        Schema::create('customer_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();  // Ví dụ: Khách sỉ, Khách lẻ, VIP, Đại lý...
            $table->text('description')->nullable(); // Mô tả loại khách hàng
            
            // Điều kiện phân loại
            $table->unsignedInteger('min_orders')->default(0);  // Số lượng đơn hàng tối thiểu
            $table->decimal('min_total_spent', 15, 2)->default(0); // Tổng chi tiêu tối thiểu
            $table->unsignedInteger('valid_days')->nullable(); // Thời hạn hiệu lực (nếu có), ví dụ 365 ngày
            
            // Chính sách ưu đãi
            $table->decimal('discount_rate', 5, 2)->default(0); // % chiết khấu cho loại khách hàng
            $table->boolean('free_shipping')->default(false);   // Có được freeship không
            $table->unsignedInteger('priority_level')->default(0); // Mức ưu tiên (VIP > thường)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_types');
    }
};
