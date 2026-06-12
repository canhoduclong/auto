<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

class WarehouseOrderAdjustmentService
{
    public function apply(Order $order, Collection $changes): void
    {
        $order->loadMissing('items');
        $itemsById = $order->items->keyBy('id');
        $variantsById = ProductVariant::query()
            ->with('product')
            ->whereIn('id', $changes->pluck('product_variant_id')->filter()->unique()->all())
            ->get()
            ->keyBy('id');

        foreach ($changes as $change) {
            $orderItemId = (int) ($change['order_item_id'] ?? 0);
            $variantId = (int) ($change['product_variant_id'] ?? 0);
            $newQty = (int) ($change['new_quantity'] ?? 0);
            $oldQty = (int) ($change['old_quantity'] ?? 0);
            $existingItem = $orderItemId > 0 ? $itemsById->get($orderItemId) : null;

            if (!$existingItem && $variantId > 0) {
                $existingItem = $order->items()->where('product_variant_id', $variantId)->first();
            }

            if ($existingItem) {
                if ($newQty <= 0) {
                    $existingItem->delete();
                    continue;
                }

                $unitWeight = (float) ($existingItem->unit_weight ?? 1);
                $isPricedByKg = (bool) ($existingItem->is_priced_by_kg ?? true);
                $factor = $isPricedByKg ? max(0.01, $unitWeight) : 1;
                $existingItem->update([
                    'quantity' => $newQty,
                    'total_weight' => round($newQty * $unitWeight, 3),
                    'total' => round((float) ($existingItem->price ?? 0) * $newQty * $factor, 2),
                ]);
                continue;
            }

            if ($variantId <= 0 || $newQty <= 0 || $oldQty > 0 || !($variant = $variantsById->get($variantId))) {
                continue;
            }

            $unitWeight = (float) ($variant->effective_kg ?? 1);
            $isPricedByKg = (bool) ($variant->effective_priced_by_kg ?? true);
            $price = (float) ($variant->final_price ?? 0);
            $factor = $isPricedByKg ? max(0.01, $unitWeight) : 1;
            $order->items()->create([
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'quantity' => $newQty,
                'price' => $price,
                'base_price' => $price,
                'unit_discount' => 0,
                'discount_type' => 'decrease',
                'discount_total' => 0,
                'unit_weight' => $unitWeight,
                'is_priced_by_kg' => $isPricedByKg,
                'total_weight' => round($newQty * $unitWeight, 3),
                'total' => round($price * $newQty * $factor, 2),
            ]);
        }

        $this->recalculateTotals($order->fresh('items'));
    }

    private function recalculateTotals(Order $order): void
    {
        $subtotal = (float) $order->items->sum(function ($item) {
            $factor = (bool) ($item->is_priced_by_kg ?? true) ? max(0.01, (float) ($item->unit_weight ?? 1)) : 1;
            return (float) ($item->base_price ?? $item->price ?? 0) * (int) $item->quantity * $factor;
        });
        $itemDiscount = (float) $order->items->sum(fn ($item) => $item->discount_total !== null
            ? (float) $item->discount_total
            : (float) (($item->unit_discount ?? 0) * ($item->quantity ?? 0)));
        $totalDiscount = $itemDiscount + (float) ($order->extra_discount_total ?? 0);
        $shipping = (bool) ($order->charge_shipping_fee ?? true) ? (float) ($order->shipping_fee ?? 0) : 0;
        $foamBox = (bool) ($order->charge_foam_box_fee ?? false) ? (float) ($order->foam_box_price ?? 0) : 0;
        $weight = (float) $order->items->sum(fn ($item) => (float) ($item->total_weight
            ?? ((float) ($item->quantity ?? 0) * (float) ($item->unit_weight ?? 0))));
        $total = max(0, round($subtotal - $totalDiscount + $shipping + $foamBox, 2));

        $order->update([
            'subtotal_amount' => round($subtotal, 2),
            'item_discount_total' => round($itemDiscount, 2),
            'total_discount' => round($totalDiscount, 2),
            'total_weight' => round($weight, 3),
            'total' => $total,
            'amount_due' => max(0, round($total - (float) ($order->amount_paid ?? 0), 2)),
        ]);
    }
}
