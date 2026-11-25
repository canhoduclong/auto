<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_assistant_keys', function (Blueprint $table) {
            $table->id();
            $table->string('label', 100);
            $table->char('token_hash', 64)->unique();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_assistant_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('key_id')->constrained('asset_assistant_keys')->restrictOnDelete();
            $table->uuid('request_id');
            $table->string('operation', 32);
            $table->string('kind', 24);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('title', 150);
            $table->json('proposed');
            $table->string('source_name', 200)->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->unique(['key_id', 'request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_assistant_drafts');
        Schema::dropIfExists('asset_assistant_keys');
    }
};
