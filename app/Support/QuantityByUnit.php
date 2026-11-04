<?php

namespace App\Support;

use Illuminate\Support\Str;

class QuantityByUnit
{
    public static function totals(iterable $rows, string $column = 'quantity'): array
    {
        $totals = [];
        foreach ($rows as $row) {
            if (($row[$column] ?? null) === null) continue;
            $raw = trim((string) ($row['unit'] ?? ''));
            $key = Str::lower(Str::ascii($raw));
            $unit = ['con' => 'con', 'bo' => 'bộ', 'cai' => 'cái'][$key] ?? ($raw !== '' ? mb_strtolower($raw) : 'Chưa rõ ĐVT');
            $totals[$unit] = ($totals[$unit] ?? 0) + (float) $row[$column];
        }
        return $totals;
    }
}
