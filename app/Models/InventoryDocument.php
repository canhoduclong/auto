<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'warehouse_id',
        'document_date',
        'notes',
        'shipping_fee',
        'user_id',
    ];

    protected $casts = [
        'document_date' => 'date',
        'shipping_fee' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(InventoryDocumentItem::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}