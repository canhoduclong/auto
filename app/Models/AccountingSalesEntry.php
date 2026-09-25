<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingSalesEntry extends Model
{
    public const SOURCE_IMPORT = 'import';
    public const SOURCE_ORDER = 'order';

    protected $fillable = [
        'entry_date', 'entry_month', 'customer_id', 'customer_code', 'customer_name',
        'sale_id', 'sale_name', 'unit', 'quantity', 'unit_weight', 'total_quantity',
        'unit_price', 'total_amount', 'source', 'source_key', 'order_id', 'order_item_id',
        'accounting_reconciliation_id', 'import_batch_id', 'import_row', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'quantity' => 'decimal:3',
        'unit_weight' => 'decimal:3',
        'total_quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function sale() { return $this->belongsTo(User::class, 'sale_id'); }
    public function order() { return $this->belongsTo(Order::class); }
    public function batch() { return $this->belongsTo(AccountingSalesImportBatch::class, 'import_batch_id'); }
}
