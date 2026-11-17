<?php

namespace App\Support;

use App\Models\Order;

class AccountingOrderAmounts
{
    /** Positive amounts reduce goods; negative amounts increase goods. */
    public static function orderDiscount(Order $order): float
    {
        if ((float) $order->order_discount > 0) {
            return (float) $order->order_discount * ($order->order_discount_type === 'increase' ? -1 : 1);
        }

        return (float) ($order->extra_discount_total ?? 0);
    }

    public static function calculate(Order $order, float $goodsAfterItemAdjustments): array
    {
        $goods = max(0, round($goodsAfterItemAdjustments, 2));
        $discount = round(min($goods, self::orderDiscount($order)), 2);
        $netGoods = round(max(0, $goods - $discount), 2);
        $vat = $order->resolvedVatAmount($netGoods);
        $customerShipping = $order->collect_customer_shipping_fee ? max(0, (float) $order->customer_shipping_fee) : 0;
        $foamBox = $order->charge_foam_box_fee ? max(0, (float) $order->foam_box_price) : 0;

        return [
            'order_discount' => $discount,
            'goods_after_discount' => $netGoods,
            'vat_amount' => $vat,
            'total' => round($netGoods + $vat + $customerShipping + $foamBox, 2),
        ];
    }
}
