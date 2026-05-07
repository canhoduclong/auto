<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskAssignment extends Model
{
    public const STATUS_DRAFT      = 'draft';
    public const STATUS_PENDING    = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_REJECTED   = 'rejected';
    public const STATUS_CANCELLED  = 'cancelled';

    public const PRIORITY_LOW    = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH   = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const PRIORITY_LABELS = [
        self::PRIORITY_LOW    => 'Thap',
        self::PRIORITY_MEDIUM => 'Trung binh',
        self::PRIORITY_HIGH   => 'Cao',
        self::PRIORITY_URGENT => 'Khan cap',
    ];

    public const STATUS_LABELS = [
        self::STATUS_DRAFT       => 'Nhap',
        self::STATUS_PENDING     => 'Cho xu ly',
        self::STATUS_IN_PROGRESS => 'Dang thuc hien',
        self::STATUS_COMPLETED   => 'Hoan thanh',
        self::STATUS_REJECTED    => 'Bi tu choi',
        self::STATUS_CANCELLED   => 'Da huy',
    ];

    protected $fillable = [
        'code', 'title', 'description', 'priority', 'status',
        'created_by', 'approval_flow_id', 'parent_id',
        'due_date', 'completed_at', 'attachments', 'reject_reason',
    ];

    protected $casts = [
        'due_date'     => 'datetime',
        'completed_at' => 'datetime',
        'attachments'  => 'array',
    ];

    // ── Relations ────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_flow_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function subTasks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function approvalSteps(): HasMany
    {
        return $this->hasMany(ApprovalOrder::class, 'task_id');
    }

    public function assignees(): HasMany
    {
        return $this->hasMany(TaskAssignee::class, 'task_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopePending($q)   { return $q->where('status', self::STATUS_PENDING); }
    public function scopeInProgress($q){ return $q->where('status', self::STATUS_IN_PROGRESS); }
    public function scopeCompleted($q) { return $q->where('status', self::STATUS_COMPLETED); }

    public function scopeVisibleTo($q, User $user): void
    {
        // Admin/CEO/Manager sees all; others see only tasks they created or are assigned in
        if ($user->hasRole('admin') || $user->hasRole('CEO') || $user->hasRole('manager')) {
            return;
        }
        $q->where(function ($inner) use ($user) {
            $inner->where('created_by', $user->id)
                  ->orWhereHas('approvalSteps', fn($s) => $s->where('approved_by', $user->id))
                  ->orWhereHas('approvalSteps.step', fn($s) => $s->where('role_slug', $user->roles->pluck('name')->first()));
        });
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast()
            && !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED, self::STATUS_REJECTED]);
    }

    public function priorityColor(): string
    {
        return match ($this->priority) {
            self::PRIORITY_URGENT => 'danger',
            self::PRIORITY_HIGH   => 'warning',
            self::PRIORITY_MEDIUM => 'info',
            default               => 'secondary',
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED   => 'success',
            self::STATUS_IN_PROGRESS => 'primary',
            self::STATUS_REJECTED    => 'danger',
            self::STATUS_CANCELLED   => 'secondary',
            self::STATUS_PENDING     => 'warning',
            default                  => 'light',
        };
    }

    // ── Code generator ───────────────────────────────────────────────

    public static function generateCode(): string
    {
        $prefix = 'TA-' . now()->format('Ymd') . '-';
        $last = self::where('code', 'like', $prefix . '%')->orderByDesc('id')->first();
        $seq = $last ? ((int) substr($last->code, -3)) + 1 : 1;
        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }
}
