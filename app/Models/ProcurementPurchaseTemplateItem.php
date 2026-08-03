<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementPurchaseTemplateItem extends Model
{
    protected $fillable = ['procurement_purchase_template_id', 'product_variant_id', 'quantity', 'weight'];
    protected $casts = ['quantity'=>'decimal:3', 'weight'=>'decimal:3'];
    public function template() { return $this->belongsTo(ProcurementPurchaseTemplate::class, 'procurement_purchase_template_id'); }
    public function productVariant() { return $this->belongsTo(ProductVariant::class); }
}
