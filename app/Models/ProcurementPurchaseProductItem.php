<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementPurchaseProductItem extends Model
{
    protected $fillable = ['procurement_purchase_id', 'product_variant_id', 'source_price_id', 'quantity', 'weight', 'unit_cost', 'line_total', 'received_quantity', 'received_weight', 'condition', 'note'];
    protected $casts = ['quantity'=>'decimal:3', 'weight'=>'decimal:3', 'unit_cost'=>'decimal:2', 'line_total'=>'decimal:2', 'received_quantity'=>'decimal:3', 'received_weight'=>'decimal:3'];
    public function purchase() { return $this->belongsTo(ProcurementPurchase::class, 'procurement_purchase_id'); }
    public function productVariant() { return $this->belongsTo(ProductVariant::class); }
    public function sourcePrice() { return $this->belongsTo(SupplierProductPrice::class, 'source_price_id'); }
}
