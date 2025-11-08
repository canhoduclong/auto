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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Tên quyền, ví dụ: manage_users
            $table->string('description')->nullable(); // Hiển thị dễ đọc hơn
            $table->string('group')->nullable(); // Nhóm tính năng: users, products, categories...
            
            $table->string('uri')->nullable(); // URI của route (vd: products/{id})
            $table->string('method')->nullable(); // GET|POST|PUT|DELETE

            $table->timestamps();
        });

        // Bảng pivot giữa roles và permissions
        Schema::create('role_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
            Schema::disableForeignKeyConstraints();
            Schema::dropIfExists('role_permission');
            Schema::dropIfExists('permissions');
            Schema::enableForeignKeyConstraints();
     
    }
};
