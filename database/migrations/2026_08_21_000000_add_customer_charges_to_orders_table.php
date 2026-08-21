<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('charge_vat')->default(false)->after('shipping_fee');
            $table->decimal('vat_percent', 5, 2)->default(0)->after('charge_vat');
            $table->decimal('vat_amount', 15, 2)->default(0)->after('vat_percent');
            $table->boolean('collect_customer_shipping_fee')->default(false)->after('vat_amount');
            $table->decimal('customer_shipping_fee', 15, 2)->default(0)->after('collect_customer_shipping_fee');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'charge_vat',
                'vat_percent',
                'vat_amount',
                'collect_customer_shipping_fee',
                'customer_shipping_fee',
            ]);
        });
    }
};
