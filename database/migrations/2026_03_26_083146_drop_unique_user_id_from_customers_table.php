<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('customers', function (Blueprint $table) {
        // Drop FK trước
        try {
            $table->dropForeign(['user_id']);
        } catch (\Exception $e) {}
    });

    // 🔥 Drop index nếu tồn tại (an toàn tuyệt đối)
    DB::statement("
        SET @idx := (
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'customers'
              AND COLUMN_NAME = 'user_id'
              AND NON_UNIQUE = 0
            LIMIT 1
        );
    ");

    DB::statement("
        SET @sql := IF(@idx IS NOT NULL,
            CONCAT('DROP INDEX ', @idx, ' ON customers'),
            'SELECT 1'
        );
    ");

    DB::statement("PREPARE stmt FROM @sql");
    DB::statement("EXECUTE stmt");
    DB::statement("DEALLOCATE PREPARE stmt");

    // Add lại FK
    Schema::table('customers', function (Blueprint $table) {
        $table->foreign('user_id')
            ->references('id')
            ->on('users')
            ->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
  public function down(): void
{
    Schema::table('customers', function (Blueprint $table) {
        try {
            $table->dropForeign(['user_id']);
        } catch (\Exception $e) {}
    });

    Schema::table('customers', function (Blueprint $table) {
        $table->unique('user_id');

        $table->foreign('user_id')
            ->references('id')
            ->on('users')
            ->onDelete('cascade');
    });
}
};
