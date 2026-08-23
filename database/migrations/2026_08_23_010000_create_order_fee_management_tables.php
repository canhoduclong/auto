<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_fee_types')) {
            Schema::create('order_fee_types', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 150);
                $table->string('code', 80)->unique();
                $table->string('calculation_type', 20)->default('fixed');
                $table->string('direction', 20)->default('charge');
                $table->decimal('default_value', 15, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_system')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['is_active', 'sort_order']);
            });
        }

        if (! Schema::hasTable('order_fees')) {
            Schema::create('order_fees', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_fee_type_id')->nullable()->constrained('order_fee_types')->nullOnDelete();
                $table->foreignId('order_adjustment_id')->nullable()->constrained('order_adjustments')->nullOnDelete();
                $table->string('fee_code', 80);
                $table->string('fee_name', 150);
                $table->string('calculation_type', 20)->default('fixed');
                $table->string('direction', 20)->default('charge');
                $table->decimal('rate', 15, 2)->default(0);
                $table->decimal('base_amount', 15, 2)->default(0);
                $table->decimal('amount', 15, 2)->default(0);
                $table->timestamps();

                $table->unique(['order_id', 'fee_code']);
                $table->index(['order_fee_type_id', 'direction']);
            });
        }

        $now = now();
        $systemTypes = [
            ['name' => 'Phí VAT', 'code' => 'vat', 'calculation_type' => 'percent', 'direction' => 'charge', 'default_value' => 10, 'sort_order' => 10, 'description' => 'Thuế VAT tính theo phần trăm tiền hàng sau chiết khấu.'],
            ['name' => 'Phí Ship', 'code' => 'shipping', 'calculation_type' => 'fixed', 'direction' => 'charge', 'default_value' => 0, 'sort_order' => 20, 'description' => 'Phí vận chuyển cộng vào tổng thanh toán của đơn.'],
            ['name' => 'Chiết khấu đơn', 'code' => 'discount', 'calculation_type' => 'fixed', 'direction' => 'discount', 'default_value' => 0, 'sort_order' => 30, 'description' => 'Khoản giảm trừ trực tiếp trên đơn hàng.'],
            ['name' => 'Phí thùng xốp', 'code' => 'foam_box', 'calculation_type' => 'fixed', 'direction' => 'charge', 'default_value' => 0, 'sort_order' => 40, 'description' => 'Phí đóng gói hoặc thùng xốp.'],
        ];

        foreach ($systemTypes as $type) {
            DB::table('order_fee_types')->updateOrInsert(
                ['code' => $type['code']],
                array_merge($type, ['is_active' => true, 'is_system' => true, 'created_at' => $now, 'updated_at' => $now])
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_fees');
        Schema::dropIfExists('order_fee_types');
    }
};
