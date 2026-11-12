<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_document_items', fn (Blueprint $table) => $table->string('unit_cost_basis', 10)->nullable());
    }

    public function down(): void
    {
        Schema::table('inventory_document_items', fn (Blueprint $table) => $table->dropColumn('unit_cost_basis'));
    }
};
