<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_ai_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_assignee_id')->unique()->constrained('task_assignees')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('task_ai_links'); }
};
