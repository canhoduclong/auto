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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('size')->nullable();       // ví dụ: 1kg, 1.5kg
            $table->string('quality')->nullable();   // ví dụ: Loại 1, Loại 2
            $table->date('production_date')->nullable(); // ngày sản xuất 
            $table->integer('stock')->default(0);    // số lượng tồn
            $table->decimal('price', 12, 2)->nullable()->default(null); // giá mặc định, cho phép null
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
