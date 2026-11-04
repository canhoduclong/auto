<?php

namespace App\Support;

use Illuminate\Support\Str;

class QuantityByUnit
{
    public static function unit(string $value): string
    {
        $raw = trim($value);
        $key = Str::lower(Str::ascii($raw));
        return ['con' => 'con', 'bo' => 'bộ', 'cai' => 'cái'][$key] ?? ($raw !== '' ? mb_strtolower($raw) : 'Chưa rõ ĐVT');
    }

    public static function hasWeight(string $unit): bool
    {
        return !in_array(self::unit($unit), ['bộ', 'cái'], true);
    }

    public static function groups(iterable $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $unit = self::unit((string) ($row['unit'] ?? ''));
            $groups[$unit] ??= ['unit' => $unit, 'quantity' => 0, 'weight' => self::hasWeight($unit) ? 0 : null,
                'amount' => 0, 'received_quantity' => null, 'received_weight' => null];
            foreach (['quantity', 'amount', 'received_quantity'] as $column) {
                if (isset($row[$column])) $groups[$unit][$column] = ($groups[$unit][$column] ?? 0) + (float) $row[$column];
            }
            if (self::hasWeight($unit)) {
                $groups[$unit]['weight'] += (float) ($row['weight'] ?? 0);
                if (isset($row['received_weight'])) $groups[$unit]['received_weight'] = ($groups[$unit]['received_weight'] ?? 0) + (float) $row['received_weight'];
            }
        }
        return array_values($groups);
    }

    public static function totals(iterable $rows, string $column = 'quantity'): array
    {
        $totals = [];
        foreach ($rows as $row) {
            if (($row[$column] ?? null) === null) continue;
            $unit = self::unit((string) ($row['unit'] ?? ''));
            $totals[$unit] = ($totals[$unit] ?? 0) + (float) $row[$column];
        }
        return $totals;
    }
}
