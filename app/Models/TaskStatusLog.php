<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'task_id',
        'from_status',
        'to_status',
        'changed_by',
        'reason',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskAssignment::class, 'task_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public static function log(TaskAssignment $task, string $toStatus, User $user, ?string $reason = null): void
    {
        self::create([
            'task_id'    => $task->id,
            'from_status' => $task->status,
            'to_status'  => $toStatus,
            'changed_by' => $user->id,
            'reason'     => $reason,
            'created_at' => now(),
        ]);
    }
}
