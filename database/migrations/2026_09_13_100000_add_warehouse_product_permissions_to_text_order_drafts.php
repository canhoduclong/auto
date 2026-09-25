<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('text_order_drafts', function (Blueprint $table) {
            $table->json('warehouse_product_permissions')->nullable()->after('parsed_items');
        });
    }

    public function down(): void
    {
        Schema::table('text_order_drafts', function (Blueprint $table) {
            $table->dropColumn('warehouse_product_permissions');
        });
    }
};
