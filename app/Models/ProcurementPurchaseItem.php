<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementPurchaseItem extends Model
{
    protected $fillable = ['procurement_purchase_id', 'stage', 'item_type', 'size', 'quantity', 'weight', 'condition', 'notes'];
    protected $casts = ['size' => 'decimal:1', 'weight' => 'decimal:3'];
    public function purchase() { return $this->belongsTo(ProcurementPurchase::class, 'procurement_purchase_id'); }
}
