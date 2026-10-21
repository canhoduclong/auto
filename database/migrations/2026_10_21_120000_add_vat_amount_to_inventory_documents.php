<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('inventory_documents', fn(Blueprint $t) => $t->decimal('vat_amount',15,2)->default(0)); }
    public function down(): void { Schema::table('inventory_documents', fn(Blueprint $t) => $t->dropColumn('vat_amount')); }
};
