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
    ];

    public function document()
    {
        return $this->belongsTo(InventoryDocument::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}