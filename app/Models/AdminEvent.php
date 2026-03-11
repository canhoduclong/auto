<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminEvent extends Model
{
    protected $fillable = [
        'actor_id',
        'event_type',
        'action',
        'subject_type',
        'subject_id',
        'title',
        'message',
        'metadata',
        'url',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
