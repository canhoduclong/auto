<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_assistant_keys', fn (Blueprint $table) => $table->text('encrypted_secret')->nullable());
    }

    public function down(): void
    {
        Schema::table('asset_assistant_keys', fn (Blueprint $table) => $table->dropColumn('encrypted_secret'));
    }
};
