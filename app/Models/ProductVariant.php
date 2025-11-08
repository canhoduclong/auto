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

    // Liên kết media cho biến thể (1 ảnh)
    public function mediaLink()
    {
        return $this->morphOne(MediaLink::class, 'model')->where('role', 'variant');
    }

    public function media()
    {
        return $this->hasOneThrough(Media::class, MediaLink::class, 'model_id', 'id', 'id', 'media_id')
            ->where('media_links.model_type', self::class)
            ->where('media_links.role', 'variant');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
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
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->latest('start_date');
    }

    // helper: lấy giá cuối cùng
    public function getFinalPriceAttribute()
    {
         return $this->priceRules()
                ->whereNull('end_date')
                ->latest('start_date')
                ->value('price');
    }



}
