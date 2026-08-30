<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleSheetsOrderSyncRun extends Model
{
    protected $fillable = [
        'business_date', 'date_field', 'synced_by', 'synced_activity_at',
        'synced_at', 'order_count', 'detail_count', 'deleted_count',
    ];

    protected $casts = [
        'business_date' => 'date',
        'synced_activity_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'synced_by');
    }
}
