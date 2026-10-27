<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('task_ai_links', function (Blueprint $table) {
            // Previously issued links without an owner binding become unusable.
            $table->foreignId('task_id')->nullable()->constrained('task_assignments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
        });
    }
    public function down(): void
    {
        Schema::table('task_ai_links', function (Blueprint $table) {
            $table->dropConstrainedForeignId('task_id');
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
