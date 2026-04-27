<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderScheduleRun extends Model
{
    protected $fillable = [
        'triggered_by',
        'trigger_type',
        'status',
        'evaluated',
        'generated',
        'need_review',
        'duration_ms',
        'error',
    ];

    protected $casts = [
        'evaluated'   => 'integer',
        'generated'   => 'integer',
        'need_review' => 'integer',
        'duration_ms' => 'integer',
    ];

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
