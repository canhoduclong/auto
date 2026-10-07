<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('task_completion_images',function(Blueprint $t){$t->foreignId('status_log_id')->nullable()->constrained('task_status_logs')->nullOnDelete();}); }
    public function down(): void { Schema::table('task_completion_images',function(Blueprint $t){$t->dropConstrainedForeignId('status_log_id');}); }
};
