<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_document_items') && ! Schema::hasColumn('inventory_document_items', 'note')) {
            Schema::table('inventory_document_items', function (Blueprint $table) {
                $table->string('note', 500)->nullable()->after('unit_cost');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_document_items') && Schema::hasColumn('inventory_document_items', 'note')) {
            Schema::table('inventory_document_items', function (Blueprint $table) {
                $table->dropColumn('note');
            });
        }
    }
};
