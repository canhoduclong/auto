<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('task_id')->nullable()->after('transaction_id');
            $table->foreign('task_id')->references('id')->on('task_assignments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('approval_orders', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
            $table->dropColumn('task_id');
        });
    }
};
