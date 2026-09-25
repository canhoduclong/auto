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
        'sort_order',
        'name',
        'unit',
        'product_type',
        'allow_adjacent_packing_sizes',
        'cutting_targets',
        'cutting_product_targets',
        'cutting_percentages',
        'cutting_percentage',
        'kg',
        'is_priced_by_kg',
        'slug',
        'description', 
        'image',
    ];

    protected $casts = [
        'allow_adjacent_packing_sizes' => 'boolean',
        'cutting_targets' => 'array',
        'cutting_product_targets' => 'array',
        'cutting_percentages' => 'array',
        'cutting_percentage' => 'float',
        'kg' => 'float',
        'is_priced_by_kg' => 'boolean',
        'status' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function cuttingPercentagesForTarget(int $mainProductId): ?array
    {
        $sides = $this->cutting_product_targets[$mainProductId] ?? null;
        if ($sides === null) return null;
        $products = self::whereIn('id', $sides)->get()->keyBy('id');
        $rates = [];
        foreach ($sides as $id) {
            $rate = $products->get($id)?->cutting_percentage;
            if ($rate === null) return null;
            $rates[$id] = (float) $rate;
        }
        if (array_sum($rates) > 100.000001) {
            throw new \RuntimeException('Tổng tỷ lệ thành phần phụ vượt 100%. Vui lòng cập nhật tỷ lệ sản phẩm pha lóc.');
        }
        $rates[$mainProductId] = round(max(0, 100 - array_sum($rates)), 6);
        return $rates;
    }

    public const TYPE_WHOLE = 'whole';
    public const TYPE_CUT = 'cut';

    public static function typeOptions(): array
    {
        return [
            self::TYPE_WHOLE => 'Nguyên con',
            self::TYPE_CUT => 'Pha lóc',
        ];
    }

    public function getProductTypeLabelAttribute(): string
    {
        return self::typeOptions()[(string) ($this->product_type ?: self::TYPE_WHOLE)] ?? 'Nguyên con';
    }

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

    public function componentRatios()
    {
        return $this->hasManyThrough(
            ProductComponentRatio::class,
            ProductVariant::class,
            'product_id',
            'source_product_variant_id',
            'id',
            'id'
        );
    }

    public function cuttingComponents()
    {
        return $this->hasMany(ProductCuttingComponent::class);
    }

    public function cuttingComponentVariants()
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'product_cutting_components',
            'product_id',
            'component_product_variant_id'
        )->withTimestamps();
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
