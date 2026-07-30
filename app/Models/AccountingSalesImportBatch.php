<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingSalesImportBatch extends Model
{
    protected $fillable = [
        'imported_by', 'business_date', 'stock_in_document_id',
        'source_warehouse_id', 'target_warehouse_id',
        'source_hash', 'row_count', 'total_amount', 'raw_text',
    ];

    protected $casts = [
        'business_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function entries()
    {
        return $this->hasMany(AccountingSalesEntry::class, 'import_batch_id');
    }

    public function importer()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'accounting_sales_import_batch_id');
    }

    public function stockInDocument()
    {
        return $this->belongsTo(InventoryDocument::class, 'stock_in_document_id');
    }

    public function sourceWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function targetWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }
}
