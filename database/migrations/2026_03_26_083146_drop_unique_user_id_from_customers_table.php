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
        Schema::table('customers', function (Blueprint $table) {
                 // drop foreign key trước
            $table->dropForeign(['user_id']);

            // drop unique
            $table->dropUnique('customers_user_id_unique');

            // tạo lại foreign key bình thường (không unique)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);

            $table->unique('user_id');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
