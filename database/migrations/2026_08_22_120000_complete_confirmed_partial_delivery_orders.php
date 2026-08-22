<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $orderIds = DB::table('order_returns as partial_returns')
            ->where('partial_returns.return_scope', 'partial')
            ->where('partial_returns.status', 'warehouse_received')
            ->whereNotExists(function ($fullReturns): void {
                $fullReturns->selectRaw('1')
                    ->from('order_returns as full_returns')
                    ->whereColumn('full_returns.order_id', 'partial_returns.order_id')
                    ->where('full_returns.return_scope', 'full')
                    ->where('full_returns.status', 'warehouse_received');
            })
            ->distinct()
            ->pluck('partial_returns.order_id');

        $orderIds->chunk(500)->each(function ($ids): void {
            DB::table('orders')
                ->whereIn('id', $ids)
                ->whereIn('status', [
                    Order::STATUS_DELIVERED,
                    Order::STATUS_RETURNING,
                    Order::STATUS_RETURNED_COMPLETED,
                ])
                ->update([
                    'status' => Order::STATUS_COMPLETED,
                    'updated_at' => now(),
                ]);

            // Shipper partial-delivery flow already stores the delivered
            // quantity/weight on order_items. Rebuild stale line totals so
            // the journal reflects only what the customer received.
            DB::table('order_items')
                ->whereIn('order_id', $ids)
                ->update([
                    'total' => DB::raw('CASE
                        WHEN is_priced_by_kg = 1
                        THEN COALESCE(actual_weight, packed_weight, total_weight, unit_weight * quantity, quantity) * price
                        ELSE quantity * price
                    END'),
                    'updated_at' => now(),
                ]);
        });
    }

    public function down(): void
    {
        // This is a corrective data migration; the previous ambiguous order
        // status and stale totals cannot be restored safely.
    }
};
