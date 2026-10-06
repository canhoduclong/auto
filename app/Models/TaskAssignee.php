<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAssignee extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'status',
        'note',
        'completed_at',
        'evaluation_score',
        'evaluated_by',
        'evaluated_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'evaluation_score' => 'integer',
        'evaluated_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskAssignment::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'completed'  => 'success',
            'in_progress', 'processing' => 'primary',
            'rejected'   => 'danger',
            'cancelled'  => 'secondary',
            default      => 'warning',
        };
    }
}
