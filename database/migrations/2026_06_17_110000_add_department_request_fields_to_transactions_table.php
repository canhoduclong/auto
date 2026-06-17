<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'request_source')) {
                $table->string('request_source', 50)->nullable()->after('reject_reason');
            }
            if (!Schema::hasColumn('transactions', 'request_department')) {
                $table->string('request_department', 100)->nullable()->after('request_source');
            }
            if (!Schema::hasColumn('transactions', 'request_title')) {
                $table->string('request_title')->nullable()->after('request_department');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('transactions', 'request_title') ? 'request_title' : null,
                Schema::hasColumn('transactions', 'request_department') ? 'request_department' : null,
                Schema::hasColumn('transactions', 'request_source') ? 'request_source' : null,
            ]);

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
