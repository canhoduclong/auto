<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'order_type')) {
                $table->string('order_type')->default('sale_order')->after('copied_from_order_id');
            }

            if (!Schema::hasColumn('orders', 'workflow_code')) {
                $table->string('workflow_code', 100)->nullable()->after('order_type');
            }

            if (!Schema::hasColumn('orders', 'is_return_order')) {
                $table->boolean('is_return_order')->default(false)->after('workflow_code');
            }

            if (!Schema::hasColumn('orders', 'parent_order_id')) {
                $table->unsignedBigInteger('parent_order_id')->nullable()->after('is_return_order');
                $table->foreign('parent_order_id')->references('id')->on('orders')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'parent_order_id')) {
                $table->dropForeign(['parent_order_id']);
                $table->dropColumn('parent_order_id');
            }

            foreach (['is_return_order', 'workflow_code', 'order_type'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
