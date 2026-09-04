<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleSheetInventoryExport extends Model
{
    protected $fillable = [
        'warehouse_id',
        'spreadsheet_id',
        'sheet_id',
        'inventory_date',
        'sheet_name',
        'written_rows_count',
        'created_by',
    ];

    protected $casts = [
        'sheet_id' => 'integer',
        'inventory_date' => 'date',
        'written_rows_count' => 'integer',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
