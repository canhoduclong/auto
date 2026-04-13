<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPriceRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'reason',
        'price',
        'min_price',
        'start_date',
        'end_date',
        'created_by',
    ];

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function product()
    {
        return $this->productVariant?->product;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
