<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add completion tracking columns to task_assignments
        Schema::table('task_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('task_assignments', 'completion_content')) {
                $table->text('completion_content')->nullable()->after('reject_reason');
            }
            if (!Schema::hasColumn('task_assignments', 'completion_notes')) {
                $table->text('completion_notes')->nullable()->after('completion_content');
            }
            if (!Schema::hasColumn('task_assignments', 'completion_verified_at')) {
                $table->timestamp('completion_verified_at')->nullable()->after('completion_notes');
            }
            if (!Schema::hasColumn('task_assignments', 'completion_verified_by')) {
                $table->unsignedBigInteger('completion_verified_by')->nullable()->after('completion_verified_at');
            }
            if (!Schema::hasColumn('task_assignments', 'rejected_reason')) {
                $table->text('rejected_reason')->nullable()->after('completion_verified_by');
            }
            
            // Add index for completion_verified_by if it doesn't exist
            if (!Schema::hasColumn('task_assignments', 'completion_verified_by')) {
                return;
            }
            try {
                $table->foreign('completion_verified_by')->references('id')->on('users')->nullOnDelete();
            } catch (\Throwable $e) {
                // Foreign key may already exist
            }
        });
    }

    public function down(): void
    {
        Schema::table('task_assignments', function (Blueprint $table) {
            $table->dropColumn(['completion_content', 'completion_notes', 'completion_verified_at', 'completion_verified_by', 'rejected_reason']);
        });
    }
};
