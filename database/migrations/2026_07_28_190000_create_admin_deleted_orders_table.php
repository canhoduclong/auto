<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_deleted_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('order_code')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('sale_user_id')->nullable()->index();
            $table->decimal('order_total', 18, 2)->default(0);
            $table->decimal('recognized_revenue', 18, 2)->default(0);
            $table->decimal('commission_amount', 18, 2)->default(0);
            $table->unsignedBigInteger('accounting_sales_import_batch_id')->nullable()->index();
            $table->string('reason', 500);
            $table->json('snapshot');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deleted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_deleted_orders');
    }
};
