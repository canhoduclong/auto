<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseInventoryTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_id',
        'product_variant_id',
        'quantity',
        'unit_cost',
    ];

    public function transfer()
    {
        return $this->belongsTo(WarehouseInventoryTransfer::class, 'transfer_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
