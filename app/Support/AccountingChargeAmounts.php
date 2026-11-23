<?php

namespace App\Support;

use App\Models\Order;

class AccountingChargeAmounts
{
    public static function weightSql(): string
    {
        return "CASE WHEN COALESCE(order_items.quantity, 0) <= 0 THEN 0 ELSE COALESCE(
            order_items.accounting_adjusted_weight,
            CASE WHEN orders.delivered_at IS NOT NULL OR orders.status IN ('delivered','completed','returning','returned','returned_completed')
                THEN order_items.delivered_weight END,
            order_items.packed_weight,
            CASE WHEN orders.status IN ('packed','packed_waiting_pickup','delivering','in_delivery','shipping','picked_up')
                THEN order_items.actual_weight END
        ) END";
    }

    /** Rebuild the charged total from the measured quantities and the existing order charges. */
    public static function forOrder(Order $order, bool $hasPackingRecord = false): ?array
    {
        $order->loadMissing(['items.product', 'items.variant.product']);
        $lines = [];
        $subtotal = 0.0;
        foreach ($order->items as $item) {
            if (! $item->effective_priced_by_kg) {
                $subtotal += (float) ($item->total ?? ((float) $item->quantity * (float) $item->price));
                continue;
            }

            $quantity = $item->accountingChargeQuantity($order, $hasPackingRecord);
            if ($quantity === null) {
                return null;
            }

            $lines[$item->id] = $amount = round($quantity * (float) $item->price, 2);
            $subtotal += $amount;
        }

        if ($lines === []) {
            return ['lines' => [], 'subtotal' => (float) $order->subtotal_amount, 'vat' => (float) $order->vat_amount, 'total' => (float) $order->total];
        }

        $amounts = AccountingOrderAmounts::calculate($order, $subtotal);
        $order->loadMissing('additionalFees');
        $customFees = $order->additionalFees->sum(fn ($fee) => ($fee->direction === 'discount' ? -1 : 1) * (float) $fee->amount);
        $internalShipping = $order->charge_shipping_fee ? max(0, (float) $order->shipping_fee) : 0;

        return [
            'lines' => $lines,
            'subtotal' => round($subtotal, 2),
            'vat' => $amounts['vat_amount'],
            'total' => round(max(0, $amounts['total'] + $customFees + $internalShipping), 2),
        ];
    }
}
