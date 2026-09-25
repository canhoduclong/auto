<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('type')->nullable(); // call, email, meeting, ...
            $table->string('subject')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); // nhân viên phụ trách
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
