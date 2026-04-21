<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Thời gian di chuyển giữa các chặng (lưu trên chặng đến)
        Schema::table('truck_route_stops', function (Blueprint $table) {
            $table->string('travel_duration', 30)->nullable()->after('arrival_time')
                ->comment('Thời gian đi từ chặng trước đến chặng này. VD: 2 tiếng, 30 phút');
        });

        // Giao hàng tận nhà cho trạm xe
        Schema::table('truck_stations', function (Blueprint $table) {
            $table->boolean('has_home_delivery')->default(false)->after('branch_info')
                ->comment('Trạm có dịch vụ giao hàng tận nhà');
            $table->decimal('home_delivery_fee', 12, 0)->default(0)->after('has_home_delivery')
                ->comment('Phí giao hàng tận nhà mặc định (0 = miễn phí)');
        });
    }

    public function down(): void
    {
        Schema::table('truck_route_stops', function (Blueprint $table) {
            $table->dropColumn('travel_duration');
        });
        Schema::table('truck_stations', function (Blueprint $table) {
            $table->dropColumn(['has_home_delivery', 'home_delivery_fee']);
        });
    }
};
