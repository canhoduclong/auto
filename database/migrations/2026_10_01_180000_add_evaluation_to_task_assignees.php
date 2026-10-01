<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_assignees', function (Blueprint $table) {
            $table->unsignedTinyInteger('evaluation_score')->nullable();
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('evaluated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('task_assignees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evaluated_by');
            $table->dropColumn(['evaluation_score', 'evaluated_at']);
        });
    }
};
