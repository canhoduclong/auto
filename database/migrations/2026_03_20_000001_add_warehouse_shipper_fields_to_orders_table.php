<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('shipper_id')->nullable()->after('user_id');
            $table->decimal('collected_amount', 15, 2)->nullable()->after('amount_due');
            $table->timestamp('delivered_at')->nullable()->after('collected_amount');
            $table->string('return_reason')->nullable()->after('delivered_at');
            $table->json('proof_images')->nullable()->after('return_reason');
            $table->string('shipper_note')->nullable()->after('proof_images');

            $table->foreign('shipper_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['shipper_id']);
            $table->dropColumn(['shipper_id', 'collected_amount', 'delivered_at', 'return_reason', 'proof_images', 'shipper_note']);
        });
    }
};
