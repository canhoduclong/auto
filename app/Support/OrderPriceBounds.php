<?php

namespace App\Support;

final class OrderPriceBounds
{
    public const ADDITIONAL_DECREASE = 12000;

    public static function minimum(float $configuredMinimum): float
    {
        return max(0, $configuredMinimum - self::ADDITIONAL_DECREASE);
    }
}
