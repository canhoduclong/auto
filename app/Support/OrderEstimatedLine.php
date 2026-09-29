<?php

namespace App\Support;

class OrderEstimatedLine
{
    /** Amounts before order-level discounts, VAT and fees. Weight is the entire line. */
    public static function amounts(int $quantity, float $weight, bool $pricedByKg, float $basePrice, float $discount, string $discountType): array
    {
        $billableQuantity = $pricedByKg ? round($weight, 3) : $quantity;
        $increase = $discountType === 'increase';
        $price = $increase ? $basePrice + $discount : $basePrice - $discount;

        return [
            'price' => $price,
            'subtotal' => round($basePrice * $billableQuantity, 2),
            'discount_total' => round(($increase ? -1 : 1) * $discount * $billableQuantity, 2),
            'total' => max(round($price * $billableQuantity, 2), 0),
        ];
    }
}
