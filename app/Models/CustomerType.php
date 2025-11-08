<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'min_orders',
        'min_total_spent',
        'valid_days',
        'discount_rate',
        'free_shipping',
        'priority_level',
    ];

    /**
     * Quan hệ: Loại khách hàng có nhiều khách hàng
     */
    public function customers()
    {
        return $this->hasMany(Customer::class, 'customer_type_id');
    }
}
