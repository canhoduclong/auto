<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('google_sheet_inventory_syncs', function (Blueprint $table): void {
            $table->foreignId('reset_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('reset_at')->nullable()->after('reset_by');
            $table->string('reset_reason', 500)->nullable()->after('reset_at');
        });
    }

    public function down(): void
    {
        Schema::table('google_sheet_inventory_syncs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reset_by');
            $table->dropColumn(['reset_at', 'reset_reason']);
        });
    }
};
