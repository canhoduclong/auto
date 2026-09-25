<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('customers', 'default_shipper_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->foreignId('default_shipper_id')
                    ->nullable()
                    ->after('current_owner_sale_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'default_shipper_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('default_shipper_id');
            });
        }
    }
};
