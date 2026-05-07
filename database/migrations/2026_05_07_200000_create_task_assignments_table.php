<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['draft', 'pending', 'in_progress', 'completed', 'rejected', 'cancelled'])
                  ->default('pending');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('approval_flow_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable(); // sub-task
            $table->timestamp('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('attachments')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('approval_flow_id')->references('id')->on('approval_flows')->nullOnDelete();
            $table->foreign('parent_id')->references('id')->on('task_assignments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignments');
    }
};
