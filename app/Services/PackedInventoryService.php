<?php
namespace App\Services;

use App\Models\{Order, Inventory, InventoryDocument, InventoryMovement, InventoryReservation, WarehouseTransfer};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class PackedInventoryService
{
    /** Call after importing the goods, inside the receiving transaction. */
    public function receiveTransfer(Order $order, WarehouseTransfer $transfer): void
    {
        $exported = InventoryMovement::query()
            ->join('inventories', 'inventories.id', '=', 'inventory_movements.inventory_id')
            ->where('reference_type', InventoryDocument::class)
            ->where('reference_id', $transfer->export_document_id ?? 0)
            ->selectRaw('inventories.product_variant_id, -SUM(inventory_movements.quantity) as exported')
            ->groupBy('inventories.product_variant_id')->pluck('exported', 'product_variant_id');

        foreach ($order->items as $item) {
            if (!$item->product_variant_id || $item->quantity <= 0) continue;
            $rows = InventoryReservation::where('order_item_id', $item->id)->lockForUpdate()->get();
            foreach ($rows as $row) {
                $source = Inventory::whereKey($row->inventory_id)->lockForUpdate()->firstOrFail();
                if ((int) $source->warehouse_id === (int) $transfer->target_warehouse_id) continue;
                if ((int) $source->warehouse_id !== (int) $transfer->source_warehouse_id) {
                    throw new \RuntimeException('Hàng giữ riêng của đơn nằm ngoài kho gửi; cần đối soát trước khi nhập.');
                }
                // Legacy pickup may already have exported stock without consuming its reservation.
                $alreadyExported = min((float) $row->quantity, max(0, (float) $exported->get($source->product_variant_id, 0)));
                $exported->put($source->product_variant_id, (float) $exported->get($source->product_variant_id, 0) - $alreadyExported);
                $deduct = (float) $row->quantity - $alreadyExported;
                if ((float) $source->quantity < $deduct) {
                    throw new \RuntimeException('Tồn kho gửi không đủ để chuyển phần hàng giữ riêng; cần đối soát.');
                }
                $row->delete();
                $source->quantity -= $deduct;
                $source->reserved_quantity = $source->reservations()->sum('quantity');
                $source->save();
                if ($deduct > 0) InventoryMovement::create([
                    'inventory_id' => $source->id, 'quantity' => -$deduct, 'type' => 'transfer_out',
                    'reference_type' => WarehouseTransfer::class, 'reference_id' => $transfer->id,
                    'user_id' => Auth::id() ?: $transfer->received_by ?: $transfer->shipper_id,
                ]);
            }

            $target = Inventory::where('warehouse_id', $transfer->target_warehouse_id)
                ->where('product_variant_id', $item->product_variant_id)->lockForUpdate()->firstOrFail();
            $held = $target->reservations()->where('order_item_id', $item->id)->sum('quantity');
            $needed = (float) $item->quantity - (float) $held;
            if ($needed < 0 || (float) $target->quantity < (float) $target->reservations()->sum('quantity') + $needed) {
                throw new \RuntimeException('Kho nhận không đủ hàng để giữ riêng cho đơn; cần đối soát.');
            }
            if ($needed > 0) {
                $reservation = new InventoryReservation([
                    'inventory_id' => $target->id, 'order_item_id' => $item->id,
                    'quantity' => $needed, 'reserved_at' => now(),
                ]);
                if (Schema::hasColumn('inventory_reservations', 'packed_at')) {
                    $reservation->forceFill(['packed_at' => now(), 'packed_weight_kg' =>
                        max(0, (float) ($item->packed_weight ?? $item->actual_weight ?? 0)) * $needed / $item->quantity]);
                }
                $reservation->save();
            }
            $target->reserved_quantity = $target->reservations()->sum('quantity');
            $target->save();
            \App\Models\ProductVariant::whereKey($item->product_variant_id)->update([
                'stock' => Inventory::where('product_variant_id', $item->product_variant_id)->sum('quantity'),
            ]);
        }
    }

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
            foreach ($rows->pluck('inventory_id')->unique() as $inventoryId) {
                $inventory = Inventory::whereKey($inventoryId)->lockForUpdate()->firstOrFail();
                $reserved = (float) $inventory->reservations()->sum('quantity');
                if ((float) $inventory->quantity < $reserved) {
                    throw new \RuntimeException('Tồn kho thực tế không đủ cho lượng đã giữ riêng. Không thể chốt đóng hàng.');
                }
                $inventory->reserved_quantity = $reserved;
                $inventory->save();
            }
            $weight = max(0, (float)($item->packed_weight ?? $item->actual_weight ?? $item->total_weight ?? 0));
            foreach ($rows as $row) {
                $row->forceFill(['packed_at' => now(), 'packed_weight_kg' => $item->quantity > 0 ? round($weight*$row->quantity/$item->quantity,3) : 0])->save();
            }
        }
    }
}
