<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'packed_image_path')) {
                $table->string('packed_image_path')->nullable()->after('qr_code');
            }

            if (!Schema::hasColumn('orders', 'delivered_image_path')) {
                $table->string('delivered_image_path')->nullable()->after('packed_image_path');
            }

            if (!Schema::hasColumn('orders', 'has_return_order')) {
                $table->boolean('has_return_order')->default(false)->after('delivered_image_path');
            }
        });

        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'receipt_image_path')) {
                $table->string('receipt_image_path')->nullable()->after('note');
            }

            if (!Schema::hasColumn('transactions', 'delivery_image_path')) {
                $table->string('delivery_image_path')->nullable()->after('receipt_image_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            foreach (['delivery_image_path', 'receipt_image_path'] as $column) {
                if (Schema::hasColumn('transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            foreach (['has_return_order', 'delivered_image_path', 'packed_image_path'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
