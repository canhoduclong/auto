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
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade'); // Liên kết khách hàng
            $table->string('project_name')->nullable();  // Tên dự án
            $table->string('zone')->nullable();          // Khu/Zone
            $table->string('block')->nullable();         // Block/Tòa
            $table->string('floor')->nullable();         // Tầng
            $table->string('unit_number')->nullable();   // Căn hộ / Số nhà

            $table->string('house_number')->nullable();          // Số nhà
            $table->string('temporary_number')->nullable();      // Số nhà tạm nếu có
            $table->text('note')->nullable();         // Thành phố

            $table->string('street')->nullable();        // Đường
            $table->string('ward')->nullable();          // Phường/Xã
            $table->string('district')->nullable();      // Quận/Huyện
            $table->string('city')->nullable();          // Thành phố
            $table->string('province')->nullable();      // Tỉnh/Thành
            $table->boolean('is_default')->default(false); // Địa chỉ mặc định
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
