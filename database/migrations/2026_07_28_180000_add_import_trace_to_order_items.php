<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'imported_name')) {
                $table->string('imported_name')->nullable()->after('product_variant_id');
            }
            if (! Schema::hasColumn('order_items', 'accounting_sales_entry_id')) {
                $table->foreignId('accounting_sales_entry_id')
                    ->nullable()
                    ->after('order_id')
                    ->unique()
                    ->constrained('accounting_sales_entries')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'accounting_sales_entry_id')) {
                $table->dropConstrainedForeignId('accounting_sales_entry_id');
            }
            if (Schema::hasColumn('order_items', 'imported_name')) {
                $table->dropColumn('imported_name');
            }
        });
    }
};
