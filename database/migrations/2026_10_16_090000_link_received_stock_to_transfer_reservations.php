<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('inventory_reservations',function(Blueprint $table){$table->unsignedBigInteger('received_transfer_id')->nullable()->index();}); }
    public function down(): void { Schema::table('inventory_reservations',fn(Blueprint $table)=>$table->dropColumn('received_transfer_id')); }
};
