<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->string('transfer_proof_path')->nullable()->after('delivery_image_path');
            $table->foreignId('transfer_proof_uploaded_by')->nullable()->after('transfer_proof_path')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('transfer_proof_uploaded_at')->nullable()->after('transfer_proof_uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropForeign(['transfer_proof_uploaded_by']);
            $table->dropColumn([
                'transfer_proof_path',
                'transfer_proof_uploaded_by',
                'transfer_proof_uploaded_at',
            ]);
        });
    }
};
