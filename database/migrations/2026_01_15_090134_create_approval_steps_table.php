<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_flow_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('step_order'); // 1,2,3
            $table->string('role_slug'); // sale_manager, director
            $table->boolean('can_skip')->default(false);
            $table->timestamps();

            $table->unique(['approval_flow_id', 'step_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_steps');
    }
};
