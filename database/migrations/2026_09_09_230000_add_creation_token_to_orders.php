<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('creation_token')->nullable();
            $table->string('creation_payload_hash', 64)->nullable();
            $table->unique(['user_id', 'creation_token']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'creation_token']);
            $table->dropColumn(['creation_token', 'creation_payload_hash']);
        });
    }
};
