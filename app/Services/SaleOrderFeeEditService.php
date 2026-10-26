<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;

class SaleOrderFeeEditService
{
    /** Only identical goods and order details may bypass the warehouse workflow. */
    public function isFeeOnlyEdit(Order $order, array $input, Collection $variants): bool
    {
        if ($order->copied_from_order_id || !$order->canBeDirectlyEditedByOwner()) return false;
        $items = $order->items;
        $submitted = collect($input['items'] ?? []);
        if ($items->count() !== $submitted->count() || $submitted->pluck('variant_id')->unique()->count() !== $submitted->count()) return false;
        foreach ($submitted as $row) {
            $item = $items->firstWhere('product_variant_id', (int) $row['variant_id']);
            $variant = $variants->get($row['variant_id']);
            if (!$item || !$variant || (int) $item->quantity !== (int) $row['quantity']) return false;
            $base = (float) ($variant->latestPriceRule?->price ?? $variant->final_price ?? 0);
            $discount = (float) ($input['item_discount'][$row['variant_id']] ?? 0);
            $type = $input['item_discount_type'][$row['variant_id']] ?? 'decrease';
            $sellingPrice = $base + ($type === 'increase' ? $discount : -$discount);
            if (abs($sellingPrice - (float) $item->price) > 0.001) return false;
            foreach (['item_total_weight' => 'total_weight', 'item_weight' => 'unit_weight'] as $key => $attribute) {
                if (isset($input[$key][$row['variant_id']]) && abs((float) $input[$key][$row['variant_id']] - (float) $item->$attribute) > 0.0005) return false;
            }
        }
        if (isset($input['warehouse_product_permissions'])) {
            $products = $items->groupBy('product_id');
            foreach ($input['warehouse_product_permissions'] as $productId => $policy) {
                if (!$products->has($productId)) return false;
                if ((bool) ($policy['quantity'] ?? false) !== $order->allowsWarehouseQuantityChange((int) $productId)) return false;
                $orderedSizes = $products->get($productId)->map(fn ($item) => (float) $variants->get($item->product_variant_id)?->size)->filter(fn ($size) => $size > 0)->all();
                $normalizeSizes = static function (array $sizes): array {
                    $sizes = array_unique(array_map(static fn ($size) => round((float) $size, 3), $sizes));
                    sort($sizes, SORT_NUMERIC);
                    return array_values($sizes);
                };
                if ($normalizeSizes(array_merge($policy['sizes'] ?? [], $orderedSizes)) !== $normalizeSizes(array_merge($order->packingSizesForProduct((int) $productId) ?? [], $orderedSizes))) return false;
            }
        }
        $ignored = ['items', 'item_discount', 'item_discount_type', 'item_total_weight', 'item_weight', 'charge_vat', 'vat_percent', 'collect_customer_shipping_fee', 'customer_shipping_fee', 'warehouse_product_permissions'];
        foreach ($input as $key => $value) {
            if (in_array($key, $ignored, true)) continue;
            $saved = $order->getAttribute($key);
            if ($key === 'delivery_date') $saved = $order->delivery_date?->toDateString();
            if ($key === 'delivery_time') { $value = substr((string) $value, 0, 5); $saved = substr((string) $saved, 0, 5); }
            if ($key === 'order_discount_type') $saved = $saved ?: 'decrease';
            if (in_array($key, ['warehouse_can_adjust', 'use_truck_station'], true)) {
                if ((bool) $value !== (bool) $saved) return false;
            } elseif (in_array($key, ['customer_id', 'order_discount', 'truck_station_id'], true)) {
                if ((float) $value !== (float) $saved) return false;
            } elseif (is_array($value) || is_array($saved)) {
                if ($this->normalizeArray($value ?: []) !== $this->normalizeArray($saved ?: [])) return false;
            } elseif (trim((string) $value) !== trim((string) $saved)) return false;
        }
        return true;
    }

    private function normalizeArray(array $value): array
    {
        ksort($value);
        foreach ($value as &$entry) $entry = is_array($entry) ? $this->normalizeArray($entry) : (string) $entry;
        return $value;
    }

    public function updateFees(Order $order, array $input): void
    {
        $oldVat = (float) $order->vat_amount;
        $oldShipping = $order->collect_customer_shipping_fee ? (float) $order->customer_shipping_fee : 0;
        if (array_key_exists('charge_vat', $input)) {
            $order->charge_vat = (bool) $input['charge_vat'];
            $order->vat_percent = $order->charge_vat ? (float) $input['vat_percent'] : 0;
        }
        if (array_key_exists('collect_customer_shipping_fee', $input)) {
            $order->collect_customer_shipping_fee = (bool) $input['collect_customer_shipping_fee'];
            $order->customer_shipping_fee = $order->collect_customer_shipping_fee ? (float) $input['customer_shipping_fee'] : 0;
        }
        $taxable = max(0, (float) $order->items->sum('total') - (float) $order->extra_discount_total);
        $order->vat_amount = $order->resolvedVatAmount($taxable);
        $shipping = $order->collect_customer_shipping_fee ? (float) $order->customer_shipping_fee : 0;
        $order->total = max(0, round((float) $order->total - $oldVat - $oldShipping + $order->vat_amount + $shipping, 2));
        $paid = (float) $order->transactions()->where('type', 'payment')->sum('amount')
            - (float) $order->transactions()->where('type', 'refund')->sum('amount');
        $order->amount_due = max(0, $order->total - $paid);
        $order->save();
    }
}
