<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskAssignment extends Model
{
    // New unified status constants based on user requirements
    public const STATUS_PENDING     = 'pending';      // Chờ thực hiện
    public const STATUS_PROCESSING  = 'processing';   // Đang thực hiện
    public const STATUS_COMPLETED   = 'completed';    // Chờ xác nhận hoàn thành (was "done")
    public const STATUS_DONE        = 'done';         // Đã hoàn thành (was "completed")
    public const STATUS_REJECTED    = 'rejected';     // Yêu cầu làm lại
    public const STATUS_CANCELLED   = 'cancelled';    // Hủy

    // Legacy status constants for backward compatibility
    public const STATUS_DRAFT       = 'draft';
    public const STATUS_IN_PROGRESS = 'processing';   // Map to processing

    public const PRIORITY_LOW    = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH   = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const PRIORITY_LABELS = [
        self::PRIORITY_LOW    => 'Thấp',
        self::PRIORITY_MEDIUM => 'Trung bình',
        self::PRIORITY_HIGH   => 'Cao',
        self::PRIORITY_URGENT => 'Khẩn cấp',
    ];

    public const STATUS_LABELS = [
        self::STATUS_PENDING     => 'Chờ thực hiện',
        self::STATUS_PROCESSING  => 'Đang thực hiện',
        self::STATUS_COMPLETED   => 'Chờ xác nhận hoàn thành',
        self::STATUS_DONE        => 'Đã hoàn thành',
        self::STATUS_REJECTED    => 'Yêu cầu làm lại',
        self::STATUS_CANCELLED   => 'Hủy',
        self::STATUS_DRAFT       => 'Nháp',
    ];

    public const STATUS_COLORS = [
        self::STATUS_PENDING     => 'secondary',  // Gray
        self::STATUS_PROCESSING  => 'primary',    // Blue
        self::STATUS_COMPLETED   => 'warning',    // Yellow/Orange
        self::STATUS_DONE        => 'success',    // Green
        self::STATUS_REJECTED    => 'danger',     // Red
        self::STATUS_CANCELLED   => 'secondary',  // Gray
        self::STATUS_DRAFT       => 'light',      // Light Gray
    ];

    protected $fillable = [
        'code', 'title', 'description', 'priority', 'status',
        'created_by', 'approval_flow_id', 'parent_id',
        'due_date', 'completed_at', 'attachments', 'reject_reason',
        'completion_content', 'completion_notes', 'completion_verified_at',
        'completion_verified_by', 'rejected_reason',
    ];

    protected $casts = [
        'due_date'                  => 'datetime',
        'completed_at'              => 'datetime',
        'completion_verified_at'    => 'datetime',
        'attachments'               => 'array',
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

    public function completionImages(): HasMany
    {
        return $this->hasMany(TaskCompletionImage::class, 'task_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(TaskStatusLog::class, 'task_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completion_verified_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopePending($q)   { return $q->where('status', self::STATUS_PENDING); }
    public function scopeProcessing($q){ return $q->where('status', self::STATUS_PROCESSING); }
    public function scopeCompleted($q) { return $q->whereIn('status', [self::STATUS_COMPLETED, self::STATUS_DONE]); }
    public function scopeDone($q)      { return $q->where('status', self::STATUS_DONE); }
    public function scopeRejected($q)  { return $q->where('status', self::STATUS_REJECTED); }
    public function scopeAwaitingVerification($q) { return $q->where('status', self::STATUS_COMPLETED); }

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

    // ── Helper Methods ───────────────────────────────────────────────

    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    public function getPriorityLabel(): string
    {
        return self::PRIORITY_LABELS[$this->priority] ?? ucfirst($this->priority);
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !in_array($this->status, [self::STATUS_DONE, self::STATUS_CANCELLED]);
    }
    /*
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast()
            && !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED, self::STATUS_REJECTED]);
    }
    */

    public function canBeCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    public function canBeVerified(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function canBeRejected(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public static function generateCode(): string
    {
        $date = now()->format('Ymd');
        $lastTask = self::whereDate('created_at', now()->toDateString())
            ->orderByDesc('id')
            ->value('code');
        
        if (!$lastTask) {
            return "TASK-{$date}-001";
        }
        
        $lastNumber = (int) substr($lastTask, -3);
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        
        return "TASK-{$date}-{$newNumber}";
    }
    
    /* 
    public static function generateCode(): string
    {
        $prefix = 'TA-' . now()->format('Ymd') . '-';
        $last = self::where('code', 'like', $prefix . '%')->orderByDesc('id')->first();
        $seq = $last ? ((int) substr($last->code, -3)) + 1 : 1;
        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }
     */

    

   

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

 
}
