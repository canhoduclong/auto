<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TruckRouteStop extends Model
{
    use HasFactory;

    protected $fillable = [
        'truck_route_id',
        'truck_station_id',
        'sort_order',
        'arrival_time',
        'travel_duration',
        'note',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(TruckRoute::class, 'truck_route_id');
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(TruckStation::class, 'truck_station_id');
    }
}
