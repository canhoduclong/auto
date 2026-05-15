<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('customers', 'shipping_fee')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->decimal('shipping_fee', 12, 2)->nullable()->after('truck_fee');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'shipping_fee')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('shipping_fee');
            });
        }
    }
};