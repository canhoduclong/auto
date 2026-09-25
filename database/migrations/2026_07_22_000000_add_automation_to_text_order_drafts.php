<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('text_order_drafts', function (Blueprint $table) {
            $table->string('automation_mode', 30)->nullable()->after('status');
            $table->boolean('automation_enabled')->default(false)->after('automation_mode');
            $table->json('automation_dates')->nullable()->after('automation_enabled');
            $table->timestamp('automation_last_run_at')->nullable()->after('automation_dates');
            $table->text('automation_last_error')->nullable()->after('automation_last_run_at');

            $table->index(['automation_enabled', 'automation_mode'], 'draft_automation_mode_index');
        });

        Schema::table('order_schedules', function (Blueprint $table) {
            $table->foreignId('text_order_draft_id')
                ->nullable()
                ->after('daily_order_schedule_id')
                ->constrained('text_order_drafts')
                ->nullOnDelete();
            $table->unique(['text_order_draft_id', 'schedule_date'], 'draft_schedule_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('order_schedules', function (Blueprint $table) {
            $table->dropUnique('draft_schedule_date_unique');
            $table->dropConstrainedForeignId('text_order_draft_id');
        });

        Schema::table('text_order_drafts', function (Blueprint $table) {
            $table->dropIndex('draft_automation_mode_index');
            $table->dropColumn([
                'automation_mode',
                'automation_enabled',
                'automation_dates',
                'automation_last_run_at',
                'automation_last_error',
            ]);
        });
    }
};
