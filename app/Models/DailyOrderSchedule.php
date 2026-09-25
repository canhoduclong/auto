<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyOrderSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'created_by',
        'approval_required',
        'is_active',
        'start_date',
        'last_processed_date',
        'meta',
    ];

    protected $casts = [
        'approval_required' => 'boolean',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'last_processed_date' => 'date',
        'meta' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(DailyOrderScheduleItem::class);
    }

    public function materializedSchedules()
    {
        return $this->hasMany(OrderSchedule::class);
    }
}