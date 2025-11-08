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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->onDelete('cascade'); // Nhân viên phụ trách
            $table->string('name');                
            $table->string('email')->unique()->nullable();
            $table->string('phone')->nullable(); 
            $table->string('address')->nullable();
            $table->date('dob')->nullable(); 
            $table->string('note')->nullable();
            $table->enum('gender', ['male','female','other'])->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active'); // Trạng thái 
            $table->foreignId('customer_type_id')->nullable()->constrained()->onDelete('set null'); // Loại khách hàng
            $table->foreignId('assigned_to')->nullable()->constrained('users'); // Nhân viên phụ trách
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
