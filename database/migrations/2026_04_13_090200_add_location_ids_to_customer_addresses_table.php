<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->foreignId('province_id')->nullable()->after('city')->constrained('provinces')->nullOnDelete();
            $table->foreignId('ward_id')->nullable()->after('province_id')->constrained('wards')->nullOnDelete();

            $table->index(['province_id', 'ward_id']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropIndex('customer_addresses_province_id_ward_id_index');
            $table->dropConstrainedForeignId('province_id');
            $table->dropConstrainedForeignId('ward_id');
        });
    }
};
