<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duck_farms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->unsignedInteger('scale')->nullable()->comment('Quy mô số con');
            $table->string('duck_breed')->nullable();
            $table->string('business_type', 50)->default('individual');
            $table->unsignedTinyInteger('raising_days')->default(45);
            $table->dateTime('last_purchase_at')->nullable()->index();
            $table->decimal('rating', 3, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('duck_farm_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duck_farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('duck_processing_conversion_rates', function (Blueprint $table) {
            $table->id();
            $table->decimal('live_size', 4, 1);
            $table->decimal('processed_size', 4, 1);
            $table->decimal('percentage', 6, 3)->default(0);
            $table->timestamps();
            $table->unique(['live_size', 'processed_size'], 'duck_conversion_size_unique');
        });

        Schema::create('procurement_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('purchase_type', ['live_duck', 'processed_duck']);
            $table->foreignId('duck_farm_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('duck_type')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('total_weight', 15, 3)->default(0);
            $table->decimal('average_weight', 8, 3)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('broker_fee', 15, 2)->default(0);
            $table->decimal('processing_fee', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('payment_status', 20)->default('unpaid');
            $table->string('duck_condition')->nullable();
            $table->dateTime('purchased_at')->index();
            $table->string('status', 30)->default('draft')->index();
            $table->dateTime('sent_to_warehouse_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('received_at')->nullable();
            $table->unsignedTinyInteger('warehouse_rating')->nullable();
            $table->text('warehouse_condition')->nullable();
            $table->text('warehouse_comment')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('procurement_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_purchase_id')->constrained()->cascadeOnDelete();
            $table->enum('stage', ['expected', 'received'])->default('expected');
            $table->enum('item_type', ['processed_duck', 'feathers', 'offal', 'reject']);
            $table->decimal('size', 4, 1)->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('weight', 15, 3)->default(0);
            $table->string('condition')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::table('roles')->updateOrInsert(
            ['name' => 'procurement_manager'],
            [
                'description' => 'Quản lý thu mua vịt',
                'layout_web_name' => 'Website / Procurement',
                'layout_web_slug' => 'website_procurement',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        foreach (['Hoàng Long TNT', 'Tân Phát'] as $supplierName) {
            if (!DB::table('suppliers')->where('name', $supplierName)->exists()) {
                DB::table('suppliers')->insert([
                    'name' => $supplierName,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_purchase_items');
        Schema::dropIfExists('procurement_purchases');
        Schema::dropIfExists('duck_processing_conversion_rates');
        Schema::dropIfExists('duck_farm_reviews');
        Schema::dropIfExists('duck_farms');
    }
};
