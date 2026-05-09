<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_completion_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->string('image_path');
            $table->string('original_filename')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('task_assignments')->cascadeOnDelete();
            $table->index(['task_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_completion_images');
    }
};
