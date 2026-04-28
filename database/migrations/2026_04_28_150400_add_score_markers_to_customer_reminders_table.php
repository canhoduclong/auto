<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_reminders', 'appointment_score_counted_at')) {
                $table->timestamp('appointment_score_counted_at')->nullable()->after('is_done');
            }
            if (!Schema::hasColumn('customer_reminders', 'meeting_score_counted_at')) {
                $table->timestamp('meeting_score_counted_at')->nullable()->after('appointment_score_counted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_reminders', function (Blueprint $table) {
            if (Schema::hasColumn('customer_reminders', 'meeting_score_counted_at')) {
                $table->dropColumn('meeting_score_counted_at');
            }
            if (Schema::hasColumn('customer_reminders', 'appointment_score_counted_at')) {
                $table->dropColumn('appointment_score_counted_at');
            }
        });
    }
};
