<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        foreach (['products', 'customers'] as $name) Schema::table($name, fn (Blueprint $t) => $t->boolean('special_pickup_enabled')->default(false));
        Schema::table('orders', function (Blueprint $t) {
            $t->boolean('is_special_pickup')->default(false);
            $t->unsignedBigInteger('special_import_document_id')->nullable();
            $t->unsignedBigInteger('special_export_document_id')->nullable();
            $t->unsignedBigInteger('special_payment_id')->nullable();
        });
    }
    public function down(): void {
        foreach (['products', 'customers'] as $name) Schema::table($name, fn (Blueprint $t) => $t->dropColumn('special_pickup_enabled'));
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['is_special_pickup', 'special_import_document_id', 'special_export_document_id', 'special_payment_id']));
    }
};
