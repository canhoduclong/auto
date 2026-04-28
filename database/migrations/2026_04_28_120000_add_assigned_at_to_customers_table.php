<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('assigned_to');
            }
        });

        DB::table('customers')
            ->whereNotNull('assigned_to')
            ->whereNull('assigned_at')
            ->update([
                'assigned_at' => DB::raw('COALESCE(updated_at, created_at, CURRENT_TIMESTAMP)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'assigned_at')) {
                $table->dropColumn('assigned_at');
            }
        });
    }
};