<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementPurchase extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent_to_warehouse';
    public const STATUS_RECEIVED = 'received';

    protected $fillable = ['code', 'purchase_type', 'duck_farm_id', 'supplier_id', 'created_by', 'warehouse_id', 'payment_transaction_id', 'duck_type', 'quantity', 'total_weight', 'average_weight', 'unit_price', 'subtotal', 'broker_fee', 'processing_fee', 'total_amount', 'payment_status', 'duck_condition', 'purchased_at', 'status', 'sent_to_warehouse_at', 'received_by', 'received_at', 'warehouse_rating', 'warehouse_condition', 'warehouse_comment', 'notes'];
    protected $casts = ['purchased_at' => 'datetime', 'sent_to_warehouse_at' => 'datetime', 'received_at' => 'datetime', 'total_weight' => 'decimal:3', 'average_weight' => 'decimal:3', 'unit_price' => 'decimal:2', 'total_amount' => 'decimal:2'];
    public function farm() { return $this->belongsTo(DuckFarm::class, 'duck_farm_id'); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function receiver() { return $this->belongsTo(User::class, 'received_by'); }
    public function paymentRequest() { return $this->belongsTo(Transaction::class, 'payment_transaction_id'); }
    public function items() { return $this->hasMany(ProcurementPurchaseItem::class); }
}
