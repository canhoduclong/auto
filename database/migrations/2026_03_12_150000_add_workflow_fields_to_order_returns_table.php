<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('order_returns', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->after('customer_id')->constrained('warehouses')->nullOnDelete();
            }

            if (!Schema::hasColumn('order_returns', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('warehouse_id')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('order_returns', 'ship_confirmed_by')) {
                $table->foreignId('ship_confirmed_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('order_returns', 'ship_confirmed_at')) {
                $table->timestamp('ship_confirmed_at')->nullable()->after('ship_confirmed_by');
            }

            if (!Schema::hasColumn('order_returns', 'warehouse_confirmed_by')) {
                $table->foreignId('warehouse_confirmed_by')->nullable()->after('ship_confirmed_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('order_returns', 'warehouse_confirmed_at')) {
                $table->timestamp('warehouse_confirmed_at')->nullable()->after('warehouse_confirmed_by');
            }

            if (!Schema::hasColumn('order_returns', 'note')) {
                $table->text('note')->nullable()->after('reason');
            }

            if (!Schema::hasColumn('order_returns', 'status')) {
                $table->string('status')->default('requested');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_returns', function (Blueprint $table) {
            foreach (['warehouse_confirmed_by', 'ship_confirmed_by', 'created_by', 'warehouse_id'] as $fk) {
                if (Schema::hasColumn('order_returns', $fk)) {
                    $table->dropConstrainedForeignId($fk);
                }
            }

            foreach (['warehouse_confirmed_at', 'ship_confirmed_at', 'note'] as $col) {
                if (Schema::hasColumn('order_returns', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
