<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipper_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->string('notes', 1000)->nullable();
            $table->timestamps();

            $table->foreign('shipper_id')->references('id')->on('users');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
        });
        // Thêm cột order_transfer_id vào bảng orders
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('order_transfer_id')->nullable()->after('shipper_id');
            $table->foreign('order_transfer_id')->references('id')->on('order_transfers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['order_transfer_id']);
            $table->dropColumn('order_transfer_id');
        });
        Schema::dropIfExists('order_transfers');
    }
};
