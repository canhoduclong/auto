<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'kg')) {
                $table->decimal('kg', 10, 2)->default(1)->after('unit');
            }

            if (!Schema::hasColumn('products', 'is_priced_by_kg')) {
                $table->boolean('is_priced_by_kg')->default(true)->after('kg');
            }
        });

        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'kg')) {
                $table->decimal('kg', 10, 2)->default(1)->after('size');
            }

            if (!Schema::hasColumn('product_variants', 'is_priced_by_kg')) {
                $table->boolean('is_priced_by_kg')->default(true)->after('kg');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'is_priced_by_kg')) {
                $table->dropColumn('is_priced_by_kg');
            }

            if (Schema::hasColumn('product_variants', 'kg')) {
                $table->dropColumn('kg');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'is_priced_by_kg')) {
                $table->dropColumn('is_priced_by_kg');
            }

            if (Schema::hasColumn('products', 'kg')) {
                $table->dropColumn('kg');
            }
        });
    }
};
