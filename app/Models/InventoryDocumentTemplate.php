<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryDocumentTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'supplier_id',
        'user_id',
        'name',
    ];

    public function items()
    {
        return $this->hasMany(InventoryDocumentTemplateItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
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
