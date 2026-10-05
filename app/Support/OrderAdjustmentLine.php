<?php

namespace App\Support;

class OrderAdjustmentLine
{
    public static function amounts(int $quantity, float $weight, float $price, bool $pricedByKg): array
    {
        $quantity = max(0, $quantity);
        $weight = $quantity === 0 ? 0.0 : max(0, $weight);

        return [
            'weight' => $weight,
            'total' => round($price * ($pricedByKg ? $weight : $quantity), 2),
        ];
    }
}
