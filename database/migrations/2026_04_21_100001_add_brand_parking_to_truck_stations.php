<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('truck_stations', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('name')
                ->constrained('truck_brands')->nullOnDelete();
            $table->decimal('parking_fee', 12, 0)->nullable()->after('phone')
                ->comment('Phí vào bãi xe');
            $table->string('branch_info')->nullable()->after('parking_fee')
                ->comment('Thông tin phòng/chi nhánh');
        });
    }

    public function down(): void
    {
        Schema::table('truck_stations', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropColumn(['brand_id', 'parking_fee', 'branch_info']);
        });
    }
};
