<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('permission_slug'); // 'assign_task', 'complete_task', 'verify_completion'
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->unique(['role_id', 'permission_slug']);
            $table->index('permission_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_permissions');
    }
};
