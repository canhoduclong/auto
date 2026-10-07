<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('task_completion_images',function(Blueprint $table){
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('explanation')->nullable();
        });
    }
    public function down(): void {
        Schema::table('task_completion_images',function(Blueprint $table){$table->dropConstrainedForeignId('uploaded_by');$table->dropColumn('explanation');});
    }
};
