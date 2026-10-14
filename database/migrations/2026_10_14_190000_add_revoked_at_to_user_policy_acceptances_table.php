<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_policy_acceptances', function (Blueprint $table): void {
            $table->timestamp('revoked_at')->nullable()->after('accepted_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('user_policy_acceptances', function (Blueprint $table): void {
            $table->dropColumn('revoked_at');
        });
    }
};
