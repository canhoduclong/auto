<?php

use App\Models\OrderAdjustment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_return_id')->nullable()->constrained('order_returns')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('workflow_code', 100)->default('order_adjustments');
            $table->string('status', 32)->default(OrderAdjustment::STATUS_DRAFT);
            $table->text('approval_note')->nullable();
            $table->text('reject_reason')->nullable();
            $table->text('adjustment_note')->nullable();
            $table->json('evidence_images')->nullable();
            $table->foreignId('return_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('warehouse_confirmation_status', 32)->default('not_required');
            $table->foreignId('warehouse_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('warehouse_confirmed_at')->nullable();
            $table->text('warehouse_confirmation_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_adjustments');
    }
};
