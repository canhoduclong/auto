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
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('type')->default('general')->after('description');
            $table->unsignedBigInteger('assigned_by')->nullable()->after('user_id');
            $table->unsignedBigInteger('assigned_to')->nullable()->after('assigned_by');
            $table->timestamp('deadline')->nullable()->after('due_date');
            $table->text('note')->nullable()->after('description');
            $table->timestamp('next_appointment')->nullable()->after('note');
            $table->json('metadata')->nullable()->after('next_appointment');
            $table->timestamp('completed_at')->nullable()->after('metadata');

            // Rename due_date to deadline if it exists
            if (Schema::hasColumn('tasks', 'due_date')) {
                $table->renameColumn('due_date', 'old_due_date');
            }

            // Add foreign keys
            $table->foreign('assigned_by')->references('id')->on('users');
            $table->foreign('assigned_to')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['assigned_by']);
            $table->dropForeign(['assigned_to']);
            $table->dropColumn(['type', 'assigned_by', 'assigned_to', 'deadline', 'note', 'next_appointment', 'metadata', 'completed_at']);

            if (Schema::hasColumn('tasks', 'old_due_date')) {
                $table->renameColumn('old_due_date', 'due_date');
            }
        });
    }
};
