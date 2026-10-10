<?php
namespace App\Services;

use App\Models\{Order, InventoryReservation};
use Illuminate\Support\Facades\DB;

class PackedInventoryService
{
    /** Must be called in the transaction which completes packing. */
    public function seal(Order $order, int $warehouseId): void
    {
        $order->loadMissing('items.packingSizeAllocations');
        foreach ($order->items as $item) {
            if (! $item->product_variant_id) continue;
            $rows = InventoryReservation::where('order_item_id', $item->id)
                ->whereHas('inventory', fn($query) => $query->where('warehouse_id', $warehouseId))
                ->lockForUpdate()->get();
            $requirements = $item->packingSizeAllocations->sum('quantity') === (int)$item->quantity
                ? $item->packingSizeAllocations->groupBy('product_variant_id')->map(fn($group) => $group->sum('quantity'))
                : collect([$item->product_variant_id => $item->quantity]);
            foreach ($requirements as $variantId => $quantity) {
                $reserved = $rows->filter(fn($row) => (int)$row->inventory->product_variant_id === (int)$variantId)->sum('quantity');
                if ((float)$reserved !== (float)$quantity) throw new \RuntimeException('Chưa giữ đủ tồn kho riêng cho mặt hàng #'.$item->id.'. Không thể chốt đóng hàng.');
            }
            $weight = max(0, (float)($item->packed_weight ?? $item->actual_weight ?? $item->total_weight ?? 0));
            foreach ($rows as $row) {
                $row->forceFill(['packed_at' => now(), 'packed_weight_kg' => $item->quantity > 0 ? round($weight*$row->quantity/$item->quantity,3) : 0])->save();
            }
        }
    }
}
