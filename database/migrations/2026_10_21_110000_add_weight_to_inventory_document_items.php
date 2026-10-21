<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory_document_items', function (Blueprint $table) {
            $table->decimal('weight_kg', 12, 3)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_document_items', fn (Blueprint $table) => $table->dropColumn('weight_kg'));
    }
};
