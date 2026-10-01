<?php

namespace App\Services;

use App\Models\Order;
use App\Models\WarehouseDispatchSlip;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WarehouseDispatchFinalizationService
{
    public function finalize(WarehouseDispatchSlip $slip, int $actorId): void
    {
        DB::transaction(function () use ($slip, $actorId): void {
            $locked = WarehouseDispatchSlip::query()->lockForUpdate()->findOrFail($slip->id);
            if ($locked->status === WarehouseDispatchSlip::STATUS_FINALIZED) {
                return;
            }
            if ($locked->status !== WarehouseDispatchSlip::STATUS_DRAFT || ! $locked->entries()->exists()) {
                throw ValidationException::withMessages(['dispatch_slip' => 'Chỉ được chốt phiếu đang mở và có nội dung bàn giao.']);
            }
            $this->loadSlip($locked);
            foreach ($locked->entries as $entry) {
                $entry->update(['snapshot' => $this->entrySnapshot($entry)]);
            }
            $locked->update([
                'status' => WarehouseDispatchSlip::STATUS_FINALIZED,
                'finalized_by' => $actorId,
                'finalized_at' => now(),
            ]);
        });
    }

    private function loadSlip(WarehouseDispatchSlip $slip): void
    {
        $slip->load([
            'sourceWarehouse:id,name,address,phone', 'targetWarehouse:id,name,address,phone',
            'shipper:id,name,short_name,phone', 'creator:id,name', 'finalizer:id,name',
            'entries.orderTransfer.orders.customer:id,name',
            'entries.orderTransfer.orders.user:id,name,short_name',
            'entries.orderTransfer.orders.items.variant.product',
            'entries.orderTransfer.orders.warehouseTransfers' => fn ($query) => $query->latest('id'),
            'entries.warehouseTransfer.order.customer:id,name',
            'entries.warehouseTransfer.order.user:id,name,short_name',
            'entries.warehouseTransfer.order.items.variant.product',
            'entries.inventoryTransfer.items.variant.product',
            'entries.inventoryTransfer.receiver:id,name',
        ]);
    }

    private function entrySnapshot($entry): array
    {
        if ($entry->orderTransfer) {
            return [
                'type' => 'order_transfer',
                'order_transfer_id' => $entry->orderTransfer->id,
                'orders' => $entry->orderTransfer->orders->map(function (Order $order): array {
                    $movement = $order->warehouseTransfers->first();

                    return [
                        'id' => $order->id,
                        'code' => $order->code ?: '#'.$order->id,
                        'customer_name' => $order->customer?->name,
                        'sale_name' => $order->user?->short_name ?: $order->user?->name,
                        'note' => $order->note,
                        'package_count' => $order->package_count,
                        'packing_specification' => $order->packing_specification,
                        'foam_box_fee' => (float) (($order->charge_foam_box_fee ?? false) ? ($order->foam_box_price ?? 0) : 0),
                        'shipping_fee' => $this->billableShippingFee($order),
                        'discount' => (float) ($order->total_discount ?? 0),
                        'item_quantity' => (int) $order->items->sum('quantity'),
                        'packed_weight' => (float) ($movement?->packed_total_weight ?? 0),
                        'items' => $order->items->filter(fn ($item) => $item->product_variant_id)->map(fn ($item) => [
                            'id' => $item->id,
                            'product_variant_id' => (int) $item->product_variant_id,
                            'product_name' => $item->variant?->product?->name ?? $item->variant?->name ?? 'Sản phẩm',
                            'sku' => $item->variant?->sku,
                            'size' => $item->variant?->size,
                            'unit' => $item->variant?->product?->unit,
                            'quantity' => (int) $item->quantity,
                            'weight' => (float) ($item->packed_weight ?? $item->total_weight ?? 0),
                            'price' => (float) ($item->price ?? 0),
                            'is_priced_by_kg' => (bool) $item->effective_priced_by_kg,
                        ])->values()->all(),
                    ];
                })->values()->all(),
            ];
        }

        if ($entry->warehouseTransfer?->order) {
            $movement = $entry->warehouseTransfer;
            $order = $movement->order;

            return [
                'type' => 'warehouse_transfer',
                'warehouse_transfer_id' => $movement->id,
                'order' => [
                    'id' => $order->id,
                    'code' => $order->code ?: '#'.$order->id,
                    'customer_name' => $order->customer?->name,
                    'sale_name' => $order->user?->short_name ?: $order->user?->name,
                    'note' => $order->note,
                    'package_count' => $order->package_count,
                    'packing_specification' => $order->packing_specification,
                    'foam_box_fee' => (float) (($order->charge_foam_box_fee ?? false) ? ($order->foam_box_price ?? 0) : 0),
                    'shipping_fee' => $this->billableShippingFee($order),
                    'discount' => (float) ($order->total_discount ?? 0),
                    'item_quantity' => (int) $order->items->sum('quantity'),
                    'packed_weight' => (float) ($movement->packed_total_weight ?? 0),
                    'items' => $order->items->filter(fn ($item) => $item->product_variant_id)->map(fn ($item) => [
                        'id' => $item->id,
                        'product_variant_id' => (int) $item->product_variant_id,
                        'product_name' => $item->variant?->product?->name ?? $item->variant?->name ?? 'Sản phẩm',
                        'sku' => $item->variant?->sku,
                        'size' => $item->variant?->size,
                        'unit' => $item->variant?->product?->unit,
                        'quantity' => (int) $item->quantity,
                        'weight' => (float) ($item->packed_weight ?? $item->actual_weight ?? $item->total_weight ?? 0),
                        'price' => (float) ($item->price ?? 0),
                        'is_priced_by_kg' => (bool) $item->effective_priced_by_kg,
                    ])->values()->all(),
                ],
            ];
        }

        $transfer = $entry->inventoryTransfer;

        return [
            'type' => 'inventory_transfer',
            'inventory_transfer' => [
                'id' => $transfer?->id,
                'code' => $transfer?->transfer_code ?: '#'.$transfer?->id,
                'note' => $transfer?->note,
                'items' => $transfer?->items->map(fn ($item) => [
                    'product_variant_id' => (int) $item->product_variant_id,
                    'product_name' => $item->variant?->product?->name ?? $item->variant?->name ?? 'Sản phẩm',
                    'sku' => $item->variant?->sku,
                    'size' => $item->variant?->size,
                    'unit' => $item->variant?->product?->unit,
                    'quantity' => (int) $item->quantity,
                    'weight_kg' => (float) $item->weight_kg,
                    'unit_cost' => (float) $item->unit_cost,
                ])->values()->all() ?? [],
            ],
        ];
    }

    private function billableShippingFee(Order $order): float
    {
        $assignedFee = (bool) ($order->charge_shipping_fee ?? false)
            ? max(0, (float) ($order->shipping_fee ?? 0))
            : 0.0;
        $customerFee = (bool) ($order->collect_customer_shipping_fee ?? false)
            ? max(0, (float) ($order->customer_shipping_fee ?? 0))
            : 0.0;

        return $assignedFee + $customerFee;
    }
}
