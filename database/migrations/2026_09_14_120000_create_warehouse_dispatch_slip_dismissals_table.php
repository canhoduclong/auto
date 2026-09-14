<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_dispatch_slip_dismissals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('warehouse_dispatch_slip_id');
            $table->foreign('warehouse_dispatch_slip_id', 'dispatch_slip_dismissal_slip_fk')
                ->references('id')->on('warehouse_dispatch_slips')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('reason');
            $table->timestamp('dismissed_at');
            $table->timestamps();

            $table->unique(['warehouse_dispatch_slip_id', 'user_id'], 'dispatch_slip_dismissal_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_dispatch_slip_dismissals');
    }
};
