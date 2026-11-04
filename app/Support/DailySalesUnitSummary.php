<?php

namespace App\Support;

use App\Enums\ProductUnit;

class DailySalesUnitSummary
{
    public static function productGroups(iterable $rows): array
    {
        $products = [];
        foreach ($rows as $row) {
            $unit = QuantityByUnit::unit((string) ($row->product_unit ?? 'cai'));
            $key = json_encode([$row->product_id ?? ($row->product_name ?? ''), $unit]);
            $products[$key][] = $row;
        }
        $result = [];
        foreach ($products as $rows) {
            $group = self::groups($rows)[0];
            $group['product_name'] = $rows[0]->product_name ?? 'Chưa rõ mặt hàng';
            $result[] = $group;
        }
        return $result;
    }

    public static function groups(iterable $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $raw = (string) ($row->product_unit ?? 'cai');
            $unit = QuantityByUnit::unit(ProductUnit::tryFrom($raw)?->label() ?? $raw);
            $groups[$unit] ??= array_fill_keys(['eff_qty', 'eff_weight', 'eff_total', 'customer_shipping_fee', 'price_adjustment', 'actual_qty', 'actual_weight', 'actual_total'], 0.0) + ['unit' => $unit];
            foreach (array_keys($groups[$unit]) as $field) {
                if ($field === 'unit') continue;
                if (in_array($field, ['eff_weight', 'actual_weight'], true) && !QuantityByUnit::hasWeight($unit)) continue;
                $groups[$unit][$field] += (float) ($row->$field ?? 0);
            }
        }
        return array_values($groups);
    }
}
