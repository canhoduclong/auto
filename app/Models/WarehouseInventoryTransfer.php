<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseInventoryTransfer extends Model
{
    use HasFactory;

    public const STATUS_PENDING_RECEIVE = 'pending_receive';
    public const STATUS_RECEIVED_COMPLETED = 'received_completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'transfer_code',
        'source_warehouse_id',
        'target_warehouse_id',
        'requested_by',
        'received_by',
        'status',
        'note',
        'export_document_id',
        'import_document_id',
        'requested_at',
        'received_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (self $transfer) {
            if ($transfer->transfer_code) {
                return;
            }

            $transfer->updateQuietly([
                'transfer_code' => 'DCK-' . now()->format('Ymd') . '-' . str_pad((string) $transfer->id, 5, '0', STR_PAD_LEFT),
            ]);
        });
    }

    public function sourceWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function targetWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items()
    {
        return $this->hasMany(WarehouseInventoryTransferItem::class, 'transfer_id');
    }

    public function exportDocument()
    {
        return $this->belongsTo(InventoryDocument::class, 'export_document_id');
    }

    public function importDocument()
    {
        return $this->belongsTo(InventoryDocument::class, 'import_document_id');
    }
}
