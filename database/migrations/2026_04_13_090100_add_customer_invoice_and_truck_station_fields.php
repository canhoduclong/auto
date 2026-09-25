<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('brand');
            $table->string('company_address')->nullable()->after('tax_code');
            $table->string('company_email')->nullable()->after('company_address');
            $table->foreignId('truck_station_id')->nullable()->after('use_truck_station')->constrained('truck_stations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('truck_station_id');
            $table->dropColumn(['company_name', 'company_address', 'company_email']);
        });
    }
};
