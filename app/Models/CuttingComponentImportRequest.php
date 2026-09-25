<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuttingComponentImportRequest extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_RECEIVED = 'received';

    protected $fillable = [
        'warehouse_id',
        'request_date',
        'status',
        'created_by',
        'received_by',
        'received_at',
        'inventory_document_id',
        'note',
    ];

    protected $casts = [
        'request_date' => 'date',
        'received_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(CuttingComponentImportRequestItem::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inventoryDocument()
    {
        return $this->belongsTo(InventoryDocument::class);
    }
}
