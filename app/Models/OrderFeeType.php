<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderFeeType extends Model
{
    public const CALCULATION_FIXED = 'fixed';
    public const CALCULATION_PERCENT = 'percent';
    public const DIRECTION_CHARGE = 'charge';
    public const DIRECTION_DISCOUNT = 'discount';

    protected $fillable = [
        'name', 'code', 'calculation_type', 'direction', 'default_value',
        'is_active', 'is_system', 'sort_order', 'description',
    ];

    protected $casts = [
        'default_value' => 'decimal:2',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function orderFees()
    {
        return $this->hasMany(OrderFee::class);
    }
}
