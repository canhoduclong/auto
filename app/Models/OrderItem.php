<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
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
        'total',
    ];

    protected $casts = [
        'actual_weight' => 'decimal:3',
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

    private function formatDisplayTotalValue(float $value): string
    {
        $formatted = number_format($value, 3, ',', '.');

        return rtrim(rtrim($formatted, '0'), ',');
    }

    public function order() { return $this->belongsTo(Order::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function variant() { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
}
