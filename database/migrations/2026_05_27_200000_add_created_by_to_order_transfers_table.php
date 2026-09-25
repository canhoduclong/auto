<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('order_transfers', 'created_by')) {
            Schema::table('order_transfers', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('order_transfers', 'created_by')) {
            Schema::table('order_transfers', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            });
        }
    }
};
