<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('delivered_weight', 12, 3)->nullable();
        });

        // Legacy actual_weight was also populated at packing and by adjustments.
        // Only copy a distinguishable delivery measurement with no completed adjustment.
        DB::table('order_items')->whereNotNull('actual_weight')->whereNotNull('packed_weight')
            ->whereColumn('actual_weight', '<>', 'packed_weight')
            ->whereExists(fn ($order) => $order->selectRaw('1')->from('orders')
                ->whereColumn('orders.id', 'order_items.order_id')->whereNotNull('orders.delivered_at'))
            ->whereNotExists(fn ($adjustment) => $adjustment->selectRaw('1')->from('order_adjustments')
                ->whereColumn('order_adjustments.order_id', 'order_items.order_id')->where('status', 'completed'))
            ->update(['delivered_weight' => DB::raw('actual_weight')]);
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('delivered_weight'));
    }
};
