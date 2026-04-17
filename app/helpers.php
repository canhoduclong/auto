<?php

if (!function_exists('format_kg')) {
    /**
     * Format a kg value for display.
     * Rules: remove trailing zeros, no space, append "kg".
     * Examples: 1.000 → "1kg", 1.250 → "1.25kg", 1.200 → "1.2kg"
     */
    function format_kg(float|int|string $value): string
    {
        $num = (float) $value;
        $str = rtrim(rtrim(number_format($num, 3, '.', ''), '0'), '.');
        return $str . 'kg';
    }
}
