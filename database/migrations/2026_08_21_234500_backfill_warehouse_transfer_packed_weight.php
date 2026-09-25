<?php

use App\Models\WarehouseTransfer;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        WarehouseTransfer::query()
            ->with(['order.items.variant.product', 'order.items.product'])
            ->whereNull('packed_total_weight')
            ->orderBy('id')
            ->chunkById(100, function ($transfers): void {
                foreach ($transfers as $transfer) {
                    if (! $transfer->order) {
                        continue;
                    }

                    WarehouseTransfer::query()
                        ->whereKey($transfer->id)
                        ->update([
                            'packed_total_weight' => $transfer->order->transferBaselineWeight(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Backfilled business snapshots must not be erased on rollback.
    }
};
