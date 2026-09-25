<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'status',
        'expand_packing_size_bounds',
    ];

    protected $casts = [
        'status' => 'boolean',
        'expand_packing_size_bounds' => 'boolean',
    ];

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function inventoryDocuments()
    {
        return $this->hasMany(InventoryDocument::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
