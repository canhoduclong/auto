<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_dispatch_slip_views', function (Blueprint $table): void {
            $table->foreignId('warehouse_dispatch_slip_id')->constrained('warehouse_dispatch_slips')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('viewed_at');
            $table->primary(['warehouse_dispatch_slip_id', 'user_id'], 'dispatch_slip_view_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_dispatch_slip_views');
    }
};
