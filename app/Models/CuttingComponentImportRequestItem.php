<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuttingComponentImportRequestItem extends Model
{
    protected $fillable = [
        'cutting_component_import_request_id',
        'cutting_batch_id',
        'order_id',
        'product_variant_id',
        'quantity',
        'source_order_code',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    public function request()
    {
        return $this->belongsTo(CuttingComponentImportRequest::class, 'cutting_component_import_request_id');
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
