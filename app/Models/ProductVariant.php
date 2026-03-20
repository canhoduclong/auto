<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'sku', 'name', 'slug', 'size', 'quality', 'production_date', 'stock']; // đã có sku, giá xử lý qua priceRules

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($variant) {
            if (empty($variant->slug)) {
                $productName = $variant->product->name;
                $attributes = $variant->values->pluck('value')->implode('-');
                $variant->slug = Str::slug($productName . '-' . $attributes . '-' . time());
            }
        });
    }

    public function mediaLink()
    {
        return $this->morphOne(MediaLink::class, 'model')->where('role', 'variant');
    }

    public function avatar()
    {
        // Backward-compatible alias used in some legacy flows.
        return $this->mediaLink();
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Alias used by warehouse product page to load stock per variant.
    public function inventory()
    {
        return $this->hasMany(Inventory::class, 'product_variant_id');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'product_variant_id');
    }

    public function values()
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variant_values', 'product_variant_id', 'product_attribute_value_id');
    }

    public function priceRules()
    {
        return $this->hasMany(ProductPriceRule::class);
    }

    public function priceLogs()
    {
        return $this->hasMany(ProductPriceLog::class);
    }
    public function latestPriceRule()
    {
        return $this->hasOne(ProductPriceRule::class, 'product_variant_id')
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', now()->toDateString());
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id');
    }

    // helper: lấy giá cuối cùng
    public function getFinalPriceAttribute()
    {
        $activeRulePrice = $this->priceRules()
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', now()->toDateString());
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->value('price');

        if ($activeRulePrice !== null) {
            return (float) $activeRulePrice;
        }

        if (array_key_exists('price', $this->attributes) && $this->attributes['price'] !== null) {
            return (float) $this->attributes['price'];
        }

        return (float) ($this->product?->default_price ?? $this->product?->price ?? 0);
    }



}
