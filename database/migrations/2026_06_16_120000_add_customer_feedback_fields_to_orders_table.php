<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('customer_feedback_status', 40)->nullable()->after('shipper_note');
            $table->text('customer_feedback_note')->nullable()->after('customer_feedback_status');
            $table->foreignId('customer_feedback_by')->nullable()->after('customer_feedback_note')->constrained('users')->nullOnDelete();
            $table->timestamp('customer_feedback_at')->nullable()->after('customer_feedback_by');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('customer_feedback_by');
            $table->dropColumn([
                'customer_feedback_status',
                'customer_feedback_note',
                'customer_feedback_at',
            ]);
        });
    }
};
