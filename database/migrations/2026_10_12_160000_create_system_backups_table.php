<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('system_backups', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('type');
            $table->string('month')->nullable()->unique(); $table->unsignedBigInteger('created_by')->nullable();
            $table->string('status')->default('pending'); $table->string('stage')->nullable();
            $table->text('error')->nullable(); $table->string('local_path')->nullable();
            $table->string('sha256',64)->nullable(); $table->unsignedBigInteger('bytes')->nullable();
            $table->string('drive_file_id')->nullable(); $table->unsignedInteger('sheet_row')->nullable();
            $table->json('manifest')->nullable(); $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable(); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('system_backups'); }
};
