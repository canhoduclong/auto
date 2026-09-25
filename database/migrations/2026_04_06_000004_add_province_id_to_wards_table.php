<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wards', function (Blueprint $table) {
            $table->foreignId('province_id')->nullable()->after('district_id')->constrained('provinces')->onDelete('cascade');
            $table->foreignId('district_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('wards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('province_id');
            $table->foreignId('district_id')->nullable(false)->change();
        });
    }
};
