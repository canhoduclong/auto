<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCuttingComponent extends Model
{
    protected $fillable = [
        'product_id',
        'component_product_variant_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function componentVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'component_product_variant_id');
    }
}
