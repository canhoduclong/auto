<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleSheetInventorySync extends Model
{
    protected $fillable = [
        'warehouse_id',
        'spreadsheet_id',
        'sheet_id',
        'inventory_date',
        'sync_number',
        'import_document_id',
        'created_by',
        'status',
        'total_positive_delta',
        'total_negative_delta',
        'applied_rows_count',
        'snapshot',
        'changes',
        'reset_by',
        'reset_at',
        'reset_reason',
    ];

    protected $casts = [
        'inventory_date' => 'date',
        'sheet_id' => 'integer',
        'sync_number' => 'integer',
        'total_positive_delta' => 'float',
        'total_negative_delta' => 'float',
        'applied_rows_count' => 'integer',
        'snapshot' => 'array',
        'changes' => 'array',
        'reset_at' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function importDocument()
    {
        return $this->belongsTo(InventoryDocument::class, 'import_document_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resetter()
    {
        return $this->belongsTo(User::class, 'reset_by');
    }
}
