<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_policy_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('policy_key', 100);
            $table->string('policy_version', 50);
            $table->timestamp('accepted_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'policy_key', 'policy_version'], 'user_policy_acceptance_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_policy_acceptances');
    }
};
