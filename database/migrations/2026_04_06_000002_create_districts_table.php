<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained('provinces')->onDelete('cascade');
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type')->nullable(); // Quận, Huyện, Thị xã, v.v.
            $table->string('old_code')->nullable(); // Mã cũ trước sáp nhập
            $table->string('old_name')->nullable(); // Tên cũ trước sáp nhập
            $table->timestamps();
            $table->index(['province_id', 'code']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
