<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('truck_routes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('truck_brand_id')->nullable()
                ->constrained('truck_brands')->nullOnDelete();
            $table->decimal('current_price', 15, 0)->nullable()->comment('Biểu giá hiện tại');
            $table->text('regulations')->nullable()->comment('Quy định vận chuyển');
            $table->text('description')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'truck_brand_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('truck_routes');
    }
};
