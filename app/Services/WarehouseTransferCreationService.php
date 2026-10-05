<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderTransfer;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Models\WarehouseInventoryTransfer;
use App\Models\WarehouseInventoryTransferItem;
use App\Models\WarehouseTransfer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WarehouseTransferCreationService
{
    public function createInventory(int $managedWarehouseId, int $targetWarehouseId, array $validated): WarehouseInventoryTransfer
    {
        $normalizedItems = collect($validated['items'])
            ->map(function (array $row) {
                return [
                    'product_variant_id' => (int) $row['product_variant_id'],
                    'quantity' => (int) $row['quantity'],
                    'weight_kg' => round((float) $row['weight_kg'], 3),
                    'unit_cost' => (float) ($row['unit_cost'] ?? 0),
                ];
            })
            ->groupBy('product_variant_id')
            ->map(function (Collection $rows, int $variantId) {
                return [
                    'product_variant_id' => $variantId,
                    'quantity' => (int) $rows->sum('quantity'),
                    'weight_kg' => round((float) $rows->sum('weight_kg'), 3),
                    'unit_cost' => (float) $rows->last()['unit_cost'],
                ];
            })
            ->values();

        return DB::transaction(function () use ($normalizedItems, $managedWarehouseId, $targetWarehouseId, $validated) {
            $targetWarehouse = Warehouse::query()->find($targetWarehouseId);

            $transfer = WarehouseInventoryTransfer::create([
                'source_warehouse_id' => $managedWarehouseId,
                'target_warehouse_id' => $targetWarehouseId,
                'requested_by' => Auth::id(),
                'status' => WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE,
                'note' => trim((string) ($validated['note'] ?? '')) ?: null,
                'requested_at' => now(),
            ]);

            $exportDocument = InventoryDocument::create([
                'type' => 'export',
                'document_date' => $validated['business_date'] ?? now()->toDateString(),
                'warehouse_id' => $managedWarehouseId,
                'supplier_id' => null,
                'shipping_fee' => 0,
                'notes' => 'Điều chuyển kho #'.($transfer->transfer_code ?? $transfer->id)
                    .' sang '.($targetWarehouse?->name ?? ('Kho #'.$targetWarehouseId)),
                'user_id' => Auth::id(),
            ]);

            foreach ($normalizedItems as $item) {
                $variantId = (int) $item['product_variant_id'];
                $qty = (int) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];

                $inventory = Inventory::query()->where([
                    'warehouse_id' => $managedWarehouseId,
                    'product_variant_id' => $variantId,
                ])->lockForUpdate()->first();

                $available = $inventory
                    ? max(0, (float) $inventory->quantity - (float) $inventory->reservations()->sum('quantity'))
                    : 0;

                if ($available < $qty) {
                    $variant = ProductVariant::query()->find($variantId);
                    throw new \RuntimeException(
                        'Không đủ tồn để điều chuyển cho '.($variant?->name ?? ('biến thể #'.$variantId))
                        .'. Tồn khả dụng: '.$available.', yêu cầu: '.$qty.'.'
                    );
                }

                WarehouseInventoryTransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_variant_id' => $variantId,
                    'quantity' => $qty,
                    'weight_kg' => (float) $item['weight_kg'],
                    'unit_cost' => $unitCost,
                ]);

                $exportDocument->items()->create([
                    'product_variant_id' => $variantId,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                ]);

                InventoryMovement::create([
                    'inventory_id' => $inventory->id,
                    'quantity' => -$qty,
                    'type' => 'transfer_out',
                    'reference_id' => $transfer->id,
                    'reference_type' => WarehouseInventoryTransfer::class,
                    'user_id' => Auth::id(),
                ]);

                $inventory->decrement('quantity', $qty);

                $totalStock = (int) Inventory::query()
                    ->where('product_variant_id', $variantId)
                    ->sum('quantity');
                ProductVariant::query()->where('id', $variantId)->update(['stock' => $totalStock]);
            }

            $transfer->update([
                'export_document_id' => $exportDocument->id,
            ]);

            return $transfer;
        });
    }

    public function createOrders(?int $sourceWarehouseId, array $data, array $orderIds): OrderTransfer
    {
        return DB::transaction(function () use ($data, $orderIds, $sourceWarehouseId) {
            $orders = $this->transferableOrders($sourceWarehouseId)
                ->whereIn('id', $orderIds)
                ->lockForUpdate()
                ->get();

            if ($orders->count() !== count($orderIds)) {
                $invalidIds = array_diff($orderIds, $orders->modelKeys());
                $visibleOrders = Order::query()->whereIn('id', $invalidIds)
                    ->when($sourceWarehouseId, fn ($query) => $query->where('warehouse_id', $sourceWarehouseId))
                    ->get()->keyBy('id');
                $reasons = collect($invalidIds)->map(function ($id) use ($visibleOrders) {
                    $order = $visibleOrders->get($id);
                    if (! $order) {
                        return 'Đơn #'.$id.': không tồn tại hoặc chưa được gán cho kho đang quản lý';
                    }
                    $reason = $order->order_transfer_id
                        ? 'đã thuộc phiếu điều chuyển #'.$order->order_transfer_id
                        : (! $order->warehouse_id ? 'chưa được gán kho' : 'trạng thái hiện tại không cho phép điều chuyển');

                    return 'Đơn '.($order->code ?: '#'.$id).': '.$reason;
                });
                throw ValidationException::withMessages([
                    'order_ids' => $reasons->implode('; ').'. Vui lòng tải lại danh sách và chọn lại đơn.',
                ]);
            }

            // Each explicit creation is a new group. Never append today's
            // selection to an existing transfer or change an earlier manifest.
            $orderTransfer = OrderTransfer::create([
                'shipper_id' => $data['shipper_id'],
                'warehouse_id' => $data['warehouse_id'],
                'notes' => trim((string) ($data['note'] ?? '')) ?: null,
                'created_by' => auth()->id(),
            ]);

            foreach ($orders as $order) {
                $order->order_transfer_id = $orderTransfer->id;
                $order->save();
                WarehouseTransfer::create([
                    'order_id' => $order->id,
                    'source_warehouse_id' => $order->warehouse_id,
                    'target_warehouse_id' => $data['warehouse_id'],
                    'shipper_id' => $data['shipper_id'],
                    'status' => WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
                    'packed_total_weight' => $order->transferBaselineWeight(),
                ]);
            }

            return $orderTransfer;
        });
    }

    public function transferableOrders(?int $warehouseId): Builder
    {
        return Order::query()
            ->whereNull('order_transfer_id')
            ->whereDoesntHave('warehouseTransfers', fn ($query) => $query->whereIn('status', [
                WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
                WarehouseTransfer::STATUS_IN_TRANSIT,
                WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
            ]))
            ->whereIn('status', ['ready_to_ship', 'packed', 'packed_waiting_pickup'])
            ->whereNotNull('warehouse_id')
            // Điều chuyển kho và lộ trình đi giao là hai nghiệp vụ độc lập.
            // Một đơn đã có lịch giao vẫn phải xuất hiện để tạo điều chuyển;
            // sau khi kho đích tiếp nhận, đơn tiếp tục lộ trình giao hiện có.
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId));
    }
}
