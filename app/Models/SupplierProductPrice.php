<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierProductPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'product_id',
        'effective_date',
        'price_calculation_type',
        'purchase_price',
        'material_price',
        'processing_cost',
        'other_cost',
        'min_price',
        'suggested_margin',
        'today_sale_price',
        'note',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'purchase_price' => 'decimal:2',
        'material_price' => 'decimal:2',
        'processing_cost' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'min_price' => 'decimal:2',
        'suggested_margin' => 'decimal:2',
        'today_sale_price' => 'decimal:2',
    ];

    public const TYPE_COMPONENT_BASED = 'component_based';
    public const TYPE_DIRECT_PURCHASE = 'direct_purchase';

    public function getStockInUnitCostAttribute(): float
    {
        if ($this->price_calculation_type === self::TYPE_DIRECT_PURCHASE) {
            return (float) ($this->purchase_price ?: $this->min_price);
        }

        return (float) $this->min_price;
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
