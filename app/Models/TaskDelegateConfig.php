<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskDelegateConfig extends Model
{
    protected $fillable = [
        'assigner_id',
        'assignee_id',
        'is_active',
        'created_by',
        'note',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigner_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Static helpers ────────────────────────────────────────────────

    /**
     * Get users that $user is allowed to assign tasks to.
     */
    public static function allowedAssignees(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return User::whereIn('id',
            self::where('assigner_id', $user->id)
                ->where('is_active', true)
                ->pluck('assignee_id')
        )->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Check whether $assigner has any delegation rights.
     */
    public static function canAssignTasks(User $user): bool
    {
        return self::where('assigner_id', $user->id)->where('is_active', true)->exists();
    }
}
