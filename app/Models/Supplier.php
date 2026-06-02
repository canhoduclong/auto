<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function inventoryDocuments()
    {
        return $this->hasMany(InventoryDocument::class);
    }

    public function supplierProducts()
    {
        return $this->hasMany(SupplierProduct::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'supplier_products')
            ->withPivot(['active', 'note'])
            ->withTimestamps();
    }

    public function prices()
    {
        return $this->hasMany(SupplierProductPrice::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
