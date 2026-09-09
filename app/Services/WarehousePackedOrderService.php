<?php

namespace App\Services;

use App\Models\Order;

class WarehousePackedOrderService
{
    public function summary(Order $order, bool $useActualWeight = false): array
    {
        $order->loadMissing('items.variant.product');
        $weightAttribute = $useActualWeight ? 'export_actual_weight' : 'warehouse_packed_weight';
        $subtotal = 0;
        $baseSubtotal = 0;
        foreach ($order->items as $item) {
            $factor = $item->effective_priced_by_kg
                ? ($item->$weightAttribute ?? $item->display_total_value)
                : (float) $item->quantity;
            $subtotal += round($factor * (float) $item->price, 2);
            $baseSubtotal += round($factor * (float) ($item->base_price ?? $item->price), 2);
        }
        $adjustment = (float) ($order->extra_discount_total ?? 0);
        if ((float) $order->order_discount > 0) {
            $adjustment = (float) $order->order_discount * ($order->order_discount_type === 'increase' ? -1 : 1);
        }
        $vat = $order->resolvedVatAmount(max(0, $subtotal - $adjustment));
        $shipping = $order->charge_shipping_fee ? max(0, (float) $order->shipping_fee) : 0;
        $customerShipping = $order->collect_customer_shipping_fee ? max(0, (float) $order->customer_shipping_fee) : 0;
        $foamBox = $order->charge_foam_box_fee ? max(0, (float) $order->foam_box_price) : 0;
        $measuredItems = $order->items->filter(fn ($item) => $item->$weightAttribute !== null);

        return [
            'items_subtotal' => round($subtotal, 2),
            'subtotal_amount' => round($baseSubtotal, 2),
            'item_discount_total' => round($baseSubtotal - $subtotal, 2),
            'extra_discount_total' => $adjustment,
            'vat_amount' => $vat,
            'shipping_fee' => $shipping + $customerShipping,
            'foam_box_fee' => $foamBox,
            'packed_weight' => $measuredItems->isEmpty() ? null : round($measuredItems->sum($weightAttribute), 3),
            'total' => round(max(0, $subtotal - $adjustment) + $vat + $shipping + $customerShipping + $foamBox, 2),
        ];
    }

    public function documentLine(\App\Models\InventoryDocumentItem $line, ?Order $order, ?float $documentVariantQuantity = null): array
    {
        $matched = $order?->items->where('product_variant_id', $line->product_variant_id) ?? collect();
        $price = (float) $line->unit_cost;
        if ($matched->isEmpty()) {
            return ['weight' => null, 'price' => $price, 'total' => round($line->quantity * $price, 2)];
        }
        $quantity = $documentVariantQuantity ?? (float) $matched->sum('quantity');
        $ratio = $quantity > 0 ? (float) $line->quantity / $quantity : 0;
        $measured = $matched->every(fn ($item) => $item->export_actual_weight !== null);
        $weight = $measured ? round($matched->sum('export_actual_weight') * $ratio, 3) : null;
        $factor = $matched->first()->effective_priced_by_kg
            ? ($weight ?? $matched->sum('display_total_value') * $ratio)
            : (float) $line->quantity;

        return ['weight' => $weight, 'price' => $price, 'total' => round($factor * $price, 2)];
    }

    public function recalculate(Order $order): void
    {
        $order->load('items.variant.product');
        foreach ($order->items as $item) {
            $factor = $item->effective_priced_by_kg
                ? ($item->warehouse_packed_weight ?? $item->display_total_value)
                : (float) $item->quantity;
            $total = round($factor * (float) $item->price, 2);
            $item->update([
                'total' => $total,
                'discount_total' => round($factor * (float) ($item->base_price ?? $item->price) - $total, 2),
            ]);
        }
        $summary = $this->summary($order);
        $order->update([
            'subtotal_amount' => $summary['subtotal_amount'],
            'item_discount_total' => $summary['item_discount_total'],
            'extra_discount_total' => $summary['extra_discount_total'],
            'total_discount' => $summary['item_discount_total'] + $summary['extra_discount_total'],
            'vat_amount' => $summary['vat_amount'],
            'total' => $summary['total'],
        ]);
    }
}
