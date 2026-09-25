<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'packed_weight')) {
                $table->decimal('packed_weight', 12, 3)->nullable()->after('actual_weight')
                      ->comment('KL kho cân khi đóng gói – không thay đổi sau khi giao shipper');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('packed_weight');
        });
    }
};
