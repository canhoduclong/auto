<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('transactions', 'request_job_title')) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->string('request_job_title', 150)->nullable()->after('request_department');
            });
        }

        DB::table('transactions')->whereNotNull('request_source')->whereNull('request_job_title')
            ->update(['request_job_title' => DB::raw('request_department')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'request_job_title')) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->dropColumn('request_job_title');
            });
        }
    }
};
