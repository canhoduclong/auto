<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('order_frequency_type', ['week', 'month'])->nullable()->after('status');
            $table->integer('order_frequency_count')->nullable()->after('order_frequency_type');
            $table->boolean('potential')->default(false)->after('order_frequency_count');
        });
    }
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['order_frequency_type', 'order_frequency_count', 'potential']);
        });
    }
};
