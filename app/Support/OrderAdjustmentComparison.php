<?php
namespace App\Support;

use App\Models\OrderAdjustment;

class OrderAdjustmentComparison
{
    public static function rows(OrderAdjustment $adjustment): array
    {
        $rows = [];
        foreach ($adjustment->items as $item) {
            $byKg = (bool) ($item->orderItem?->effective_priced_by_kg ?? $item->variant?->effective_priced_by_kg ?? true);
            $originalQuantity = (float) $item->original_quantity;
            $adjustedQuantity = (float) $item->adjusted_quantity;
            $originalWeight = $byKg && $originalQuantity > 0 ? (float) $item->original_weight : 0;
            $adjustedWeight = $byKg && $adjustedQuantity > 0 ? (float) ($item->adjusted_weight ?? $originalWeight) : 0;
            $packedQuantity = (float) ($item->orderItem?->packed_quantity ?? $originalQuantity);
            $packedWeight = $byKg ? $item->orderItem?->packed_weight : null;
            $rows[] = [
                'item'=>$item,
                'name'=>$item->variant?->product?->name ?? $item->orderItem?->imported_name ?? 'Sản phẩm',
                'variant'=>$item->variant?->name, 'sku'=>$item->variant?->sku,
                'size'=>$item->variant?->size,
                'byKg'=>$byKg,
                'originalQuantity'=>$originalQuantity, 'adjustedQuantity'=>$adjustedQuantity,
                'originalPrice'=>(float) $item->original_price, 'adjustedPrice'=>(float) $item->adjusted_price,
                'originalWeight'=>$originalWeight, 'adjustedWeight'=>$adjustedWeight,
                'packedQuantity'=>$packedQuantity, 'packedWeight'=>$packedWeight === null ? null : (float) $packedWeight,
                'originalSize'=>$byKg && $packedQuantity > 0 && $packedWeight !== null ? (float) $packedWeight / $packedQuantity : null,
                'adjustedSize'=>$byKg && $adjustedQuantity > 0 ? $adjustedWeight / $adjustedQuantity : null,
                'new'=>!$item->order_item_id,
                'changed'=>!$item->order_item_id || abs($originalQuantity-$adjustedQuantity)>0.0001 || abs((float)$item->original_price-(float)$item->adjusted_price)>0.0001 || ($byKg && abs($originalWeight-$adjustedWeight)>0.0001),
            ];
        }
        return $rows;
    }
    public static function completeRows(OrderAdjustment $adjustment): array
    {
        $rows = self::rows($adjustment);
        foreach ($adjustment->order?->items ?? [] as $item) {
            if ($adjustment->items->contains('order_item_id', $item->id)
                || $adjustment->items->contains(fn($change) => !$change->order_item_id && (int)$change->product_variant_id === (int)$item->product_variant_id)) continue;
            $byKg = $item->effective_priced_by_kg;
            $quantity = (float)$item->quantity;
            $weight = $byKg && $quantity > 0 ? (float)($item->actual_weight ?? $item->total_weight ?? 0) : 0;
            $packedQuantity = (float)($item->packed_quantity ?? $quantity);
            $size = $byKg && $packedQuantity > 0 && $item->packed_weight !== null ? (float)$item->packed_weight / $packedQuantity : null;
            $rows[] = ['item'=>null,'name'=>$item->variant?->product?->name ?? $item->imported_name ?? 'Sản phẩm','variant'=>$item->variant?->name, 'sku'=>$item->variant?->sku,'size'=>$item->variant?->size,
                'byKg'=>$byKg,'originalQuantity'=>$quantity,'adjustedQuantity'=>$quantity,'originalWeight'=>$weight,'adjustedWeight'=>$weight,
                'originalPrice'=>(float)$item->price,'adjustedPrice'=>(float)$item->price,'originalSize'=>$size,'adjustedSize'=>$size,
                'packedQuantity'=>$packedQuantity,'packedWeight'=>$item->packed_weight,'new'=>false,'changed'=>false];
        }
        foreach ($rows as &$row) {
            $row['originalTotal'] = round($row['originalPrice'] * ($row['byKg'] ? $row['originalWeight'] : $row['originalQuantity']), 2);
            $row['adjustedTotal'] = round($row['adjustedPrice'] * ($row['byKg'] ? $row['adjustedWeight'] : $row['adjustedQuantity']), 2);
            if (!$row['changed']) $row['adjustedSize'] = $row['originalSize'];
        }
        return $rows;
    }

    public static function totals(OrderAdjustment $adjustment, array $rows, string $state): array
    {
        $order = $adjustment->order;
        $subtotal = array_sum(array_column(array_filter($rows, fn($row) => $state !== 'original' || !$row['new']), $state.'Total'));
        $definitions = [
            'discount'=>['name'=>'Chiết khấu đơn','direction'=>'discount','value'=>(float)($order?->extra_discount_total ?? 0),'enabled'=>(float)($order?->extra_discount_total ?? 0)>0],
            'vat'=>['name'=>'VAT','value'=>(float)($order?->vat_percent ?: $order?->vat_amount ?? 0),'enabled'=>(bool)$order?->charge_vat,'calculation_type'=>$order?->vat_percent > 0 ? 'percent' : 'fixed'],
            'shipping'=>array_merge(['name'=>'Phí giao hàng thu khách'], $order ? \App\Services\OrderFeeService::customerShippingState($order) : ['enabled'=>false,'value'=>0]),
            'foam_box'=>['name'=>'Phí thùng xốp','value'=>(float)($order?->foam_box_price ?? 0),'enabled'=>(bool)$order?->charge_foam_box_fee],
        ];
        foreach ($order?->additionalFees ?? [] as $fee) $definitions[$fee->fee_code] = ['name'=>$fee->fee_name,'value'=>(float)$fee->rate,'enabled'=>true,'calculation_type'=>$fee->calculation_type,'direction'=>$fee->direction];
        foreach ((array)$adjustment->fee_changes as $code=>$change) {
            if ($code === 'shipping' && $order) $change = \App\Services\OrderFeeService::shippingChange($order, $change);
            $definitions[$code] = array_merge($definitions[$code] ?? [], array_intersect_key($change,array_flip(['name','direction','calculation_type'])),(array)($change[$state] ?? []));
        }
        $discount = $definitions['discount']['enabled'] ? (float)$definitions['discount']['value'] : 0;
        $base = max(0,$subtotal-$discount);
        $fees = [];
        $total = $subtotal;
        foreach ($definitions as $code=>$fee) {
            if (!($fee['enabled'] ?? false)) continue;
            $amount = ($fee['calculation_type'] ?? 'fixed') === 'percent' ? round($base * min(100,max(0,(float)$fee['value'])) / 100,2) : max(0,(float)$fee['value']);
            if (($fee['direction'] ?? 'charge') === 'discount') $amount = -($code === 'discount' ? min($subtotal, $amount) : $amount);
            $fees[] = ['code'=>$code,'name'=>$fee['name'] ?? $code,'amount'=>$amount];
            $total += $amount;
        }
        return ['subtotal'=>$subtotal,'fees'=>$fees,'total'=>round(max(0,$total),2)];
    }

}
