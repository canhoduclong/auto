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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('block_id')->nullable()->after('team_id');
            $table->unsignedBigInteger('department_id')->nullable()->after('block_id');
            $table->foreign('block_id')->references('id')->on('blocks')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['block_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn(['block_id', 'department_id']);
        });
    }
};
