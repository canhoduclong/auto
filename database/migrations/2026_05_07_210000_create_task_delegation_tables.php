<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin-defined rules: which user can assign tasks to which users
        Schema::create('task_delegate_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assigner_id');  // user who is allowed to assign
            $table->unsignedBigInteger('assignee_id');  // user who can receive tasks from assigner
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');   // admin who created this rule
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['assigner_id', 'assignee_id']);
            $table->foreign('assigner_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('assignee_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });

        // Per-task assignees (many-to-many with status tracking)
        Schema::create('task_assignees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'rejected'])->default('pending');
            $table->text('note')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['task_id', 'user_id']);
            $table->foreign('task_id')->references('id')->on('task_assignments')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignees');
        Schema::dropIfExists('task_delegate_configs');
    }
};
