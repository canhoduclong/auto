<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'sku', 'name', 'slug', 'size', 'quality', 'production_date', 'stock', 'kg', 'is_priced_by_kg', 'sort_order']; // đã có sku, giá xử lý qua priceRules

    protected $casts = [
        'kg' => 'float',
        'is_priced_by_kg' => 'boolean',
        'status' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeWithAvailableStock(Builder $query): Builder
    {
        return $query->addSelect([
            'available_stock' => Inventory::query()
                ->selectRaw('COALESCE(SUM(quantity - reserved_quantity), 0)')
                ->whereColumn('product_variant_id', 'product_variants.id'),
        ]);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->whereHas('inventories', function ($inventoryQuery) {
            $inventoryQuery->whereRaw('(quantity - reserved_quantity) > 0');
        });
    }

    protected static function booted(): void
    {
        static::creating(function (ProductVariant $variant) {
            if (!empty($variant->slug)) {
                return;
            }

            $productName = $variant->product?->name ?? 'variant';
            $sku = $variant->sku ?? '';
            $size = $variant->size ?? '';
            $attributes = [$productName, $sku, $size];

            $baseSlug = Str::slug(implode('-', array_filter($attributes, fn ($value) => filled($value))));
            $baseSlug = $baseSlug !== '' ? $baseSlug : 'variant';

            $slug = $baseSlug;
            $suffix = 2;

            while (static::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $suffix;
                $suffix++;
            }

            $variant->slug = $slug;
        });
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

    public function getAvailableStockAttribute(): int
    {
        if (array_key_exists('available_stock', $this->attributes)) {
            return max(0, (int) $this->attributes['available_stock']);
        }

        if ($this->relationLoaded('inventories')) {
            return (int) $this->inventories->sum(function (Inventory $inventory) {
                return max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity);
            });
        }

        return (int) $this->inventories()
            ->selectRaw('COALESCE(SUM(quantity - reserved_quantity), 0) as available_sum')
            ->value('available_sum');
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

    public function latestPriceLog()
    {
        return $this->hasOne(ProductPriceLog::class)->latestOfMany('applied_at');
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

    public function componentRatios()
    {
        return $this->hasMany(ProductComponentRatio::class, 'source_product_variant_id');
    }

    public function asComponentRatios()
    {
        return $this->hasMany(ProductComponentRatio::class, 'component_product_variant_id');
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

    public function getUnitLabelAttribute(): string
    {
        return strtolower($this->product?->unit_label ?? 'cai');
    }

    public function getEffectiveKgAttribute(): float
    {
        $variantKg = (float) ($this->kg ?? 0);
        if ($variantKg > 0) {
            return $variantKg;
        }

        $productKg = (float) ($this->product?->kg ?? 0);
        if ($productKg > 0) {
            return $productKg;
        }

        return 1.0;
    }

    public function getEffectivePricedByKgAttribute(): bool
    {
        if ($this->is_priced_by_kg !== null) {
            return (bool) $this->is_priced_by_kg;
        }

        return (bool) ($this->product?->is_priced_by_kg ?? true);
    }

    public function mediaLinks()
    {
        return $this->morphMany(MediaLink::class, 'model');
    }

    public function mediaLink()
    {
        return $this->morphOne(MediaLink::class, 'model')->where('role', 'variant')->with('media');
    }

    public function avatar()
    {
        return $this->mediaLink();
    }

    public function getMediaAttribute()
    {
        return $this->mediaLink?->media;
    }

    public function getMediaIdAttribute()
    {
        return $this->mediaLink?->media_id;
    }

    public function getMediaUrlAttribute()
    {
        return $this->media?->url;
    }

}
