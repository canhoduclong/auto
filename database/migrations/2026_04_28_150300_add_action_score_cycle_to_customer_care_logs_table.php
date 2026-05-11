<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_care_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_care_logs', 'action_type')) {
                $table->string('action_type', 32)->default('note')->after('note');
            }
            if (!Schema::hasColumn('customer_care_logs', 'score_earned')) {
                $table->integer('score_earned')->default(0)->after('action_type');
            }
            if (!Schema::hasColumn('customer_care_logs', 'cycle_no')) {
                $table->unsignedInteger('cycle_no')->default(1)->after('score_earned');
            }
            if (!Schema::hasColumn('customer_care_logs', 'meta')) {
                $table->json('meta')->nullable()->after('cycle_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_care_logs', function (Blueprint $table) {
            if (Schema::hasColumn('customer_care_logs', 'meta')) {
                $table->dropColumn('meta');
            }
            if (Schema::hasColumn('customer_care_logs', 'cycle_no')) {
                $table->dropColumn('cycle_no');
            }
            if (Schema::hasColumn('customer_care_logs', 'score_earned')) {
                $table->dropColumn('score_earned');
            }
            if (Schema::hasColumn('customer_care_logs', 'action_type')) {
                $table->dropColumn('action_type');
            }
        });
    }
};
