<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductComponentRatio extends Model
{
    protected $fillable = [
        'source_product_variant_id',
        'component_product_variant_id',
        'standard_weight',
        'percentage',
    ];

    protected $casts = [
        'standard_weight' => 'float',
        'percentage' => 'float',
    ];

    public function sourceVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'source_product_variant_id');
    }

    public function componentVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'component_product_variant_id');
    }
}
