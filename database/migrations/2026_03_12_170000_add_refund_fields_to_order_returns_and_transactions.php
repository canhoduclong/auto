<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('order_returns', 'refund_amount')) {
                $table->decimal('refund_amount', 15, 2)->default(0)->after('note');
            }

            if (!Schema::hasColumn('order_returns', 'return_scope')) {
                $table->string('return_scope', 20)->nullable()->after('refund_amount');
            }
        });

        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'order_return_id')) {
                $table->foreignId('order_return_id')->nullable()->after('order_id')->constrained('order_returns')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'order_return_id')) {
                $table->dropConstrainedForeignId('order_return_id');
            }
        });

        Schema::table('order_returns', function (Blueprint $table) {
            foreach (['refund_amount', 'return_scope'] as $column) {
                if (Schema::hasColumn('order_returns', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
