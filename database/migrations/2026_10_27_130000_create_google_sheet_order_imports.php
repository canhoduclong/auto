<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->text('old_name')->nullable();
        });
        Schema::create('google_sheet_order_imports', function (Blueprint $table) {
            $table->id();
            $table->string('source_key', 64)->unique();
            $table->date('order_date');
            $table->string('source_customer');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('imported_by');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('google_sheet_order_imports');
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn('old_name'));
    }
};
