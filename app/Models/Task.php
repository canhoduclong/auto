<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $fillable = [
        'title',
        'description',
        'type',
        'status',
        'assigned_by',
        'assigned_to',
        'deadline',
        'customer_id',
        'note',
        'next_appointment',
        'metadata',
        'completed_at'
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'next_appointment' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Relationships
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeAssignedBy($query, $userId)
    {
        return $query->where('assigned_by', $userId);
    }

    // Helper methods
    public function isOverdue(): bool
    {
        return $this->deadline && $this->deadline->isPast() && !in_array($this->status, ['completed', 'cancelled']);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => 'secondary',
            'in_progress' => 'primary',
            'completed' => 'success',
            'overdue' => 'danger',
            'cancelled' => 'warning',
            default => 'secondary'
        };
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            'kpi' => 'KPI',
            'customer_task' => 'Task khách hàng',
            'general' => 'Chung',
            default => ucfirst($this->type)
        };
    }
}
