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
                'variant'=>$item->variant?->name,
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
}
