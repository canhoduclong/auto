<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'accounting_sales_entry_id',
        'product_id',
        'product_variant_id',
        'imported_name',
        'quantity',
        'price',
        'base_price',
        'unit_discount',
        'discount_type',
        'discount_total',
        'unit_weight',
        'is_priced_by_kg',
        'total_weight',
        'actual_weight',
        'packed_weight',
        'total',
    ];

    protected $casts = [
        'actual_weight'  => 'decimal:3',
        'packed_weight'  => 'decimal:3',
        'is_priced_by_kg' => 'boolean',
    ];

    public function getEffectivePricedByKgAttribute(): bool
    {
        if ($this->is_priced_by_kg !== null) {
            return (bool) $this->is_priced_by_kg;
        }

        if ($this->variant) {
            return (bool) ($this->variant->effective_priced_by_kg ?? true);
        }

        return (bool) ($this->product?->is_priced_by_kg ?? true);
    }

    public function getEffectiveUnitWeightAttribute(): float
    {
        $unitWeight = (float) ($this->unit_weight ?? 0);
        if ($unitWeight > 0) {
            return $unitWeight;
        }

        if ($this->variant) {
            $variantKg = (float) ($this->variant->effective_kg ?? 0);
            if ($variantKg > 0) {
                return $variantKg;
            }
        }

        $productKg = (float) ($this->product?->kg ?? 0);
        if ($productKg > 0) {
            return $productKg;
        }

        return 1.0;
    }

    public function getDisplayTotalValueAttribute(): float
    {
        $quantity = (float) ($this->quantity ?? 0);
        $factor = $this->effective_priced_by_kg ? $this->effective_unit_weight : 1;

        return round($quantity * $factor, 3);
    }

    public function getDisplayTotalUnitAttribute(): string
    {
        if ($this->effective_priced_by_kg) {
            return 'kg';
        }

        return $this->product?->unit_label
            ?? $this->variant?->product?->unit_label
            ?? 'Cái';
    }

    public function getDisplayTotalLabelAttribute(): string
    {
        return $this->formatDisplayTotalValue($this->display_total_value) . ' ' . $this->display_total_unit;
    }

    /**
     * Use the measurement belonging to the order's current workflow stage.
     * packed_weight preserves the warehouse measurement, while actual_weight
     * is overwritten with the delivered/customer measurement at delivery.
     */
    public function displayValueForStage(string $status): float
    {
        if (! $this->effective_priced_by_kg) {
            return max(0, (float) ($this->quantity ?? 0));
        }

        if (in_array($status, $this->deliveryMeasuredStatuses(), true)) {
            if ($this->actual_weight !== null) {
                return max(0, (float) $this->actual_weight);
            }

            if ($this->packed_weight !== null) {
                return max(0, (float) $this->packed_weight);
            }
        }

        if (in_array($status, $this->warehouseMeasuredStatuses(), true)) {
            if ($this->packed_weight !== null) {
                return max(0, (float) $this->packed_weight);
            }

            if ($this->actual_weight !== null) {
                return max(0, (float) $this->actual_weight);
            }
        }

        return (float) $this->display_total_value;
    }

    public function displayLabelForStage(string $status): string
    {
        return $this->formatDisplayTotalValue($this->displayValueForStage($status))
            .' '.$this->display_total_unit;
    }

    public function displaySourceForStage(string $status): string
    {
        if (in_array($status, $this->deliveryMeasuredStatuses(), true)
            && ($this->actual_weight !== null || ! $this->effective_priced_by_kg)
        ) {
            return 'Thực giao / khách cân';
        }

        if (in_array($status, $this->warehouseMeasuredStatuses(), true)
            && $this->effective_priced_by_kg
            && ($this->packed_weight !== null || $this->actual_weight !== null)
        ) {
            return 'Kho cân';
        }

        return 'Theo đơn';
    }

    public function lineTotalForStage(string $status): float
    {
        if ($this->effective_priced_by_kg) {
            return round($this->displayValueForStage($status) * (float) ($this->price ?? 0), 2);
        }

        $lineTotal = (float) ($this->total ?? 0);

        return $lineTotal > 0
            ? $lineTotal
            : round((float) ($this->quantity ?? 0) * (float) ($this->price ?? 0), 2);
    }

    private function warehouseMeasuredStatuses(): array
    {
        return [
            Order::STATUS_PACKING,
            Order::STATUS_PACKED,
            Order::STATUS_READY_TO_SHIP,
            Order::STATUS_DELIVERING,
            Order::STATUS_IN_DELIVERY,
            'shipping',
            'picked_up',
        ];
    }

    private function deliveryMeasuredStatuses(): array
    {
        return [
            Order::STATUS_DELIVERED,
            Order::STATUS_COMPLETED,
            Order::STATUS_RETURNING,
            Order::STATUS_RETURNED,
            Order::STATUS_RETURNED_COMPLETED,
        ];
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->product?->name
            ?? $this->variant?->product?->name
            ?? $this->imported_name
            ?? $this->variant?->name
            ?? 'Sản phẩm';
    }

    private function formatDisplayTotalValue(float $value): string
    {
        $formatted = number_format($value, 3, ',', '.');

        return rtrim(rtrim($formatted, '0'), ',');
    }

    public function order() { return $this->belongsTo(Order::class); }
    public function accountingSalesEntry() { return $this->belongsTo(AccountingSalesEntry::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function variant() { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function packingSizeAllocations() { return $this->hasMany(OrderItemPackingSizeAllocation::class); }
}
