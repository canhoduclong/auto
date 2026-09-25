<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 100);
            $table->string('action', 100);
            $table->nullableMorphs('subject');
            $table->string('title');
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'action']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_events');
    }
};
