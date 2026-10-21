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
        'weight_kg',
        'unit_cost',
        'note',
        'source_price_id',
    ];

    protected $casts = [
        'quantity' => 'float',
        'weight_kg' => 'float',
        'unit_cost' => 'float',
    ];

    public function getImportWeightKgAttribute(): float
    {
        return max(0, $this->weight_kg !== null
            ? (float) $this->weight_kg
            : round((float) $this->quantity * (float) ($this->productVariant?->effective_kg ?? 1), 3));
    }

    public function getImportLineTotalAttribute(): float
    {
        $basis = ($this->productVariant?->effective_priced_by_kg ?? false)
            ? $this->import_weight_kg
            : (float) $this->quantity;

        return round($basis * (float) $this->unit_cost, 2);
    }

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
