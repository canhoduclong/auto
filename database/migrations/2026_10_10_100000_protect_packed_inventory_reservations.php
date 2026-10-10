<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('inventory_reservations', function(Blueprint $table) {
            $table->timestamp('packed_at')->nullable()->index();
            $table->decimal('packed_weight_kg', 15, 3)->default(0);
        });
    }
    public function down(): void {
        Schema::table('inventory_reservations', function(Blueprint $table) { $table->dropColumn(['packed_at','packed_weight_kg']); });
    }
};
