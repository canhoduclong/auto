<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPresenceLog extends Model
{
    protected $fillable = [
        'user_id',
        'source',
        'observed_at',
        'ip_address',
        'user_agent',
        'reason',
        'route',
    ];

    protected $casts = [
        'observed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
