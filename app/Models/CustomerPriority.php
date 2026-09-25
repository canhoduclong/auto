<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPriority extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'sale_id',
        'priority_level',
        'care_score',
        'start_date',
        'expire_date',
        'last_activity_at',
        'is_active',
        'takeover_eligible',
        'cycle_no',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'expire_date' => 'datetime',
        'last_activity_at' => 'datetime',
        'is_active' => 'boolean',
        'takeover_eligible' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale()
    {
        return $this->belongsTo(User::class, 'sale_id');
    }
}
