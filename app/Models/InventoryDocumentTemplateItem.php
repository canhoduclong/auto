<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryDocumentTemplateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_document_template_id',
        'product_variant_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function template()
    {
        return $this->belongsTo(InventoryDocumentTemplate::class, 'inventory_document_template_id');
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
