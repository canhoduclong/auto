<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_id',
        'quantity',
        'weight_kg',
        'reason',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'float',
        'weight_kg' => 'float',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
