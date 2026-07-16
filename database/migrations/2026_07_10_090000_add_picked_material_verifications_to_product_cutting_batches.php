<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_cutting_batches', function (Blueprint $table): void {
            if (!Schema::hasColumn('product_cutting_batches', 'picked_material_verifications')) {
                $table->json('picked_material_verifications')->nullable()->after('source_materials');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_cutting_batches', function (Blueprint $table): void {
            if (Schema::hasColumn('product_cutting_batches', 'picked_material_verifications')) {
                $table->dropColumn('picked_material_verifications');
            }
        });
    }
};
