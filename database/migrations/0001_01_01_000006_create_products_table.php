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
        Schema::create('products', function (Blueprint $table) {
            $table->id();            
            // Liên kết với bảng users
            $table->foreignId('user_id')
                    ->constrained() // Tạo ràng buộc khóa ngoại, đảm bảo user_id phải tồn tại trong bảng users.
                    ->onDelete('cascade');  // Khi một người dùng bị xóa, tất cả sản phẩm của người đó cũng sẽ bị xóa theo. Điều này giúp giữ cho cơ sở dữ liệu luôn nhất quán

                    // Liên kết với bảng categories
            $table->foreignId('category_id')->nullable()->constrained();
            $table->string('name')->nullable();
            $table->string('slug')->unique(); // Dùng cho URL thân thiện
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2); // Giá sản phẩm
            $table->integer('stock')->default(0); // Số lượng tồn kho
            $table->string('image')->nullable(); // Đường dẫn hình ảnh chính
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
