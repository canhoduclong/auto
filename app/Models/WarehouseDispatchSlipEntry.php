<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseDispatchSlipEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_dispatch_slip_id', 'order_transfer_id', 'warehouse_transfer_id',
        'inventory_transfer_id', 'snapshot',
    ];

    protected $casts = ['snapshot' => 'array'];

    public function slip()
    {
        return $this->belongsTo(WarehouseDispatchSlip::class, 'warehouse_dispatch_slip_id');
    }

    public function orderTransfer()
    {
        return $this->belongsTo(OrderTransfer::class);
    }

    public function inventoryTransfer()
    {
        return $this->belongsTo(WarehouseInventoryTransfer::class);
    }

    public function warehouseTransfer()
    {
        return $this->belongsTo(WarehouseTransfer::class);
    }
}
