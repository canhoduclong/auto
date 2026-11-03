<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('task_assignments', function (Blueprint $table) {
            $table->foreignId('publication_approver_id')->nullable()->constrained('users');
            $table->string('publication_status', 20)->nullable();
            $table->json('publication_assignee_ids')->nullable();
            $table->timestamp('published_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('task_assignments', function (Blueprint $table) {
            $table->dropForeign(['publication_approver_id']);
            $table->dropColumn(['publication_approver_id', 'publication_status', 'publication_assignee_ids', 'published_at']);
        });
    }
};
