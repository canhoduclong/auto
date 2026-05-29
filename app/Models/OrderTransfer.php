<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderTransfer extends Model
{
    use HasFactory;
    protected $fillable = [
        'shipper_id',
        'warehouse_id',
        'notes',
        'created_by',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'order_transfer_id');
    }

    public function shipper()
    {
        return $this->belongsTo(User::class, 'shipper_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
