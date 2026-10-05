<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only repair lines already removed by a completed adjustment. Pending
        // requests and warehouse returns not yet applied must retain their charges.
        DB::table('orders')->select('orders.id', 'orders.code')
            ->whereExists(function ($query): void {
                $query->selectRaw('1')->from('order_items')
                    ->whereColumn('order_items.order_id', 'orders.id')
                    ->where('order_items.quantity', 0)
                    ->where(function ($values): void {
                        $values->where('order_items.total', '>', 0)
                            ->orWhere('order_items.total_weight', '>', 0)
                            ->orWhere('order_items.actual_weight', '>', 0)
                            ->orWhere('order_items.packed_weight', '>', 0);
                    })
                    ->whereExists(function ($adjustments): void {
                        $adjustments->selectRaw('1')->from('order_adjustment_items')
                            ->join('order_adjustments', 'order_adjustments.id', '=', 'order_adjustment_items.order_adjustment_id')
                            ->whereColumn('order_adjustment_items.order_item_id', 'order_items.id')
                            ->whereColumn('order_adjustments.order_id', 'orders.id')
                            ->where('order_adjustment_items.adjusted_quantity', 0)
                            ->where('order_adjustments.status', 'completed');
                    });
            })
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    if (Artisan::call('orders:repair-zero-quantity', ['code' => $order->code, '--apply' => true]) !== 0) {
                        throw new RuntimeException('Không sửa được đơn '.$order->code.': '.Artisan::output());
                    }
                }
            }, 'orders.id', 'id');
    }

    public function down(): void
    {
        // Do not restore invalid charges when rolling back application code.
    }
};
