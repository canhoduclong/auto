<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'inventory_id',
        'quantity',
        'reserved_at',
    ];

    protected $casts = ['packed_at' => 'datetime', 'packed_weight_kg' => 'float'];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function recoveryDocuments()
    {
        return $this->hasMany(InventoryDocument::class);
    }
}
