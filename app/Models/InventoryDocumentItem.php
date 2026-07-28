<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryDocumentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_document_id',
        'product_variant_id',
        'quantity',
        'unit_cost',
        'note',
        'source_price_id',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_cost' => 'float',
    ];

    public function document()
    {
        return $this->belongsTo(InventoryDocument::class, 'inventory_document_id');
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function sourcePrice()
    {
        return $this->belongsTo(SupplierProductPrice::class, 'source_price_id');
    }
}
