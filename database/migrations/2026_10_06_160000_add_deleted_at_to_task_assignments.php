<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasColumn('task_assignments', 'deleted_at')) {
            Schema::table('task_assignments', fn (Blueprint $table) => $table->softDeletes());
        }
    }
    public function down(): void {
        Schema::table('task_assignments', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
