<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('status', ['pending_approval', 'approved', 'rejected'])->default('approved')->after('delivery_image_path');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('submitted_by');
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete()->after('approved_by');
            $table->timestamp('approved_at')->nullable()->after('rejected_by');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->text('reject_reason')->nullable()->after('rejected_at');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropColumns(['status', 'submitted_by', 'approved_by', 'rejected_by', 'approved_at', 'rejected_at', 'reject_reason']);
        });
    }
};
