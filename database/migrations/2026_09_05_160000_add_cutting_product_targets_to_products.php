<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('products', fn (Blueprint $table) => $table->json('cutting_product_targets')->nullable()); }
    public function down(): void { Schema::table('products', fn (Blueprint $table) => $table->dropColumn('cutting_product_targets')); }
};
