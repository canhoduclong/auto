<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('text_order_drafts', function (Blueprint $table) {
            $table->boolean('charge_vat')->default(false);
            $table->decimal('vat_percent', 5, 2)->default(0);
            $table->boolean('collect_customer_shipping_fee')->default(false);
            $table->decimal('customer_shipping_fee', 14, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('text_order_drafts', function (Blueprint $table) {
            $table->dropColumn(['charge_vat', 'vat_percent', 'collect_customer_shipping_fee', 'customer_shipping_fee']);
        });
    }
};
