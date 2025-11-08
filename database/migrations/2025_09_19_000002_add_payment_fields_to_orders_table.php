<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('amount_paid', 15, 2)->default(0)->after('total');
            $table->decimal('amount_due', 15, 2)->default(0)->after('amount_paid');
            $table->string('payment_method')->nullable()->after('amount_due');
            $table->string('payment_status')->default('unpaid')->after('payment_method'); // unpaid, partial, paid
        });
    }
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['amount_paid', 'amount_due', 'payment_method', 'payment_status']);
        });
    }
};
