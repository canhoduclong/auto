<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'request_items')) {
                $table->json('request_items')->nullable()->after('request_title');
            }
            if (!Schema::hasColumn('transactions', 'request_subtotal')) {
                $table->decimal('request_subtotal', 15, 2)->nullable()->after('request_items');
            }
            if (!Schema::hasColumn('transactions', 'request_vat')) {
                $table->decimal('request_vat', 15, 2)->nullable()->after('request_subtotal');
            }
            if (!Schema::hasColumn('transactions', 'request_total')) {
                $table->decimal('request_total', 15, 2)->nullable()->after('request_vat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'request_total')) {
                $table->dropColumn('request_total');
            }
            if (Schema::hasColumn('transactions', 'request_vat')) {
                $table->dropColumn('request_vat');
            }
            if (Schema::hasColumn('transactions', 'request_subtotal')) {
                $table->dropColumn('request_subtotal');
            }
            if (Schema::hasColumn('transactions', 'request_items')) {
                $table->dropColumn('request_items');
            }
        });
    }
};
