<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (!Schema::hasColumn('products', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('status');
                $table->index('sort_order');
            }
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            if (!Schema::hasColumn('product_variants', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('status');
                $table->index(['product_id', 'sort_order']);
            }
        });

        if (!Schema::hasTable('user_product_variant_preferences')) {
            Schema::create('user_product_variant_preferences', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
                $table->boolean('is_pinned')->default(false);
                $table->unsignedInteger('sort_order')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'product_variant_id'], 'user_variant_pref_unique');
                $table->index(['user_id', 'is_pinned', 'sort_order'], 'user_variant_pref_order_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_product_variant_preferences');

        Schema::table('product_variants', function (Blueprint $table): void {
            if (Schema::hasColumn('product_variants', 'sort_order')) {
                $table->dropIndex(['product_id', 'sort_order']);
                $table->dropColumn('sort_order');
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'sort_order')) {
                $table->dropIndex(['sort_order']);
                $table->dropColumn('sort_order');
            }
        });
    }
};
