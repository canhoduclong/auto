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

if (!function_exists('current_accounting_route_prefix')) {
    function current_accounting_route_prefix(): string
    {
        $routeName = request()->route()?->getName() ?? '';

        return str_starts_with($routeName, 'admin.accounting.')
            ? 'admin.accounting.'
            : 'accounting.';
    }
}

if (!function_exists('accounting_route_name')) {
    function accounting_route_name(string $name, ?string $prefix = null): string
    {
        return rtrim($prefix ?? current_accounting_route_prefix(), '.') . '.' . ltrim($name, '.');
    }
}

if (!function_exists('accounting_route')) {
    function accounting_route(string $name, $parameters = [], bool $absolute = true, ?string $prefix = null): string
    {
        return route(accounting_route_name($name, $prefix), $parameters, $absolute);
    }
}

if (!function_exists('accounting_layout')) {
    function accounting_layout(): string
    {
        return current_accounting_route_prefix() === 'admin.accounting.'
            ? 'layouts.admin-accounting'
            : 'layouts.accounting';
    }
}
