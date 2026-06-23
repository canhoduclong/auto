<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DuckFarm extends Model
{
    protected $fillable = ['name', 'phone', 'address', 'scale', 'duck_breed', 'business_type', 'raising_days', 'last_purchase_at', 'rating', 'notes', 'is_active'];
    protected $casts = ['last_purchase_at' => 'datetime', 'rating' => 'decimal:2', 'is_active' => 'boolean'];
    public function purchases() { return $this->hasMany(ProcurementPurchase::class); }
    public function reviews() { return $this->hasMany(DuckFarmReview::class); }
}
