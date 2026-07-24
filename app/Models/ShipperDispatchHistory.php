<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipperDispatchHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_date',
        'version',
        'route_plan',
        'notes',
        'shippers_count',
        'trips_count',
        'orders_count',
        'total_fee',
        'created_by',
        'published_at',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'route_plan' => 'array',
        'total_fee' => 'decimal:2',
        'published_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
