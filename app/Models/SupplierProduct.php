<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'product_id',
        'active',
        'price_calculation_type',
        'note',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public const TYPE_COMPONENT_BASED = 'component_based';
    public const TYPE_DIRECT_PURCHASE = 'direct_purchase';

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
