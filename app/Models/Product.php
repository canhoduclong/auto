<?php

namespace App\Models;

use App\Enums\ProductUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'status',
        'name',
        'unit',
        'kg',
        'is_priced_by_kg',
        'slug',
        'description', 
        'image',
    ];

    protected $casts = [
        'kg' => 'float',
        'is_priced_by_kg' => 'boolean',
        'status' => 'boolean',
    ];

    public function getUnitLabelAttribute(): string
    {
        $unit = ProductUnit::tryFrom((string) $this->unit);

        return $unit?->label() ?? ProductUnit::CAI->label();
    }

    public function formatQuantity(float|int $quantity, int $decimals = 0): string
    {
        return number_format($quantity, $decimals, ',', '.') . ' ' . strtolower($this->unit_label);
    }

    public function getWeightUnitLabelAttribute(): string
    {
        // Business rule: Con/Cai => weight shown in Kg, others keep their own unit.
        if (in_array((string) $this->unit, ['con', 'cai'], true)) {
            return 'Kg';
        }

        return $this->unit_label;
    }
    
    // Tạo slug tự động nếu chưa có
    public static function boot()
    {
        parent::boot();
        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name) . '-' . uniqid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function mediaLinks()
    {
        return $this->morphMany(MediaLink::class, 'model');
    }

    public function thumbnail()
    {
        return $this->morphOne(MediaLink::class, 'model')
                    ->where('role', 'thumbnail')
                    ->with('media');
    }

    public function avatar()
    {
        return $this->morphOne(MediaLink::class, 'model')
                    ->where('role', 'avatar')
                    ->with('media');
    }
    
    public function gallery()
    {
        return $this->morphMany(MediaLink::class, 'model')
                    ->where('role', 'gallery')
                    ->with('media');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function supplierProducts()
    {
        return $this->hasMany(SupplierProduct::class);
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'supplier_products')
            ->withPivot(['active', 'note'])
            ->withTimestamps();
    }

    public function supplierPrices()
    {
        return $this->hasMany(SupplierProductPrice::class);
    }
    
    // lấy biến thể mặc định (nếu chỉ có 1 biến thể)
    public function defaultVariant()
    {
        return $this->variants()->first();
    }




}
