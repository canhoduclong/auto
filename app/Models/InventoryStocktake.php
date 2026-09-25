<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryStocktake extends Model
{
    use HasFactory;

    public const STATUS_COMPLETED = 'completed';
    public const TYPE_OPENING = 'opening';
    public const TYPE_CLOSING = 'closing';

    protected $fillable = [
        'code',
        'warehouse_id',
        'counted_at',
        'stocktake_type',
        'status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'counted_at' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(InventoryStocktakeItem::class, 'stocktake_id');
    }
}
