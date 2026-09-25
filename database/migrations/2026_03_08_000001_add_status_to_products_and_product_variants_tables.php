<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'status')) {
                $table->boolean('status')->default(true)->after('category_id');
            }
        });

        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'status')) {
                $table->boolean('status')->default(true)->after('price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
