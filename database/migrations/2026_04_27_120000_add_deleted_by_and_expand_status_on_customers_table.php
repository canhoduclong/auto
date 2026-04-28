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
            if (!Schema::hasColumn('customers', 'deleted_by')) {
                if (Schema::hasColumn('customers', 'deleted_at')) {
                    $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
                } else {
                    $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_at');
                }
            }
        });

        if (Schema::hasColumn('customers', 'status')) {
            DB::statement("ALTER TABLE customers MODIFY status ENUM('active','processing','archived','inactive') NOT NULL DEFAULT 'active'");
        } else {
            Schema::table('customers', function (Blueprint $table) {
                $table->enum('status', ['active', 'processing', 'archived', 'inactive'])
                    ->default('active')
                    ->after('gender');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'deleted_by')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('deleted_by');
            });
        }

        if (Schema::hasColumn('customers', 'status')) {
            DB::statement("ALTER TABLE customers MODIFY status ENUM('active','inactive') NOT NULL DEFAULT 'active'");
        }
    }
};
