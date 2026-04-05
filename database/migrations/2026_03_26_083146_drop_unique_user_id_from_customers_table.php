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
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            Schema::table('customers', function (Blueprint $table) {
                try {
                    $table->dropForeign(['user_id']);
                } catch (QueryException $e) {
                    // Ignore missing foreign key in SQLite test schema.
                }
            });

            DB::statement('DROP INDEX IF EXISTS customers_user_id_unique');

            Schema::table('customers', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });

            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique('customers_user_id_unique');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            Schema::table('customers', function (Blueprint $table) {
                try {
                    $table->dropForeign(['user_id']);
                } catch (QueryException $e) {
                    // Ignore missing foreign key in SQLite test schema.
                }

                $table->unique('user_id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });

            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unique('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
