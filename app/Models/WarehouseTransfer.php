<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseTransfer extends Model
{
    use HasFactory;

    public const STATUS_PENDING_SHIPPER_PICKUP = 'pending_shipper_pickup';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_DELIVERED_WAITING_RECEIVE = 'delivered_waiting_receive';

    public const STATUS_RECEIVED_COMPLETED = 'received_completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_id',
        'source_warehouse_id',
        'target_warehouse_id',
        'shipper_id',
        'status',
        'note',
        'shipper_pickup_note',
        'shipper_delivery_note',
        'delivery_proof_image',
        'export_document_id',
        'import_document_id',
        'picked_up_by',
        'picked_up_at',
        'delivered_by',
        'delivered_at',
        'received_by',
        'received_at',
        'packed_total_weight',
        'received_total_weight',
        'weight_loss',
        'received_weights',
    ];

    protected $casts = [
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'received_at' => 'datetime',
        'packed_total_weight' => 'decimal:3',
        'received_total_weight' => 'decimal:3',
        'weight_loss' => 'decimal:3',
        'received_weights' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function sourceWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function targetWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }

    public function shipper()
    {
        return $this->belongsTo(User::class, 'shipper_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function dispatchEntry()
    {
        return $this->hasOne(WarehouseDispatchSlipEntry::class);
    }
}
