<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalWorkflow extends Model
{
    use HasFactory;

    public const ACTIVITY_ORDER_CREATE = 'order_create';
    public const ACTIVITY_TASK_ASSIGNMENT = 'task_assignment';
    public const ACTIVITY_ORDER_ADJUSTMENT_REQUEST = 'order_adjustment_request';
    public const ACTIVITY_TRANSACTION_CREATE = 'transaction_create';

    public const ACTIVITY_LABELS = [
        self::ACTIVITY_ORDER_CREATE => 'Tao don hang moi',
        self::ACTIVITY_TASK_ASSIGNMENT => 'Giao viec',
        self::ACTIVITY_ORDER_ADJUSTMENT_REQUEST => 'Yeu cau dieu chinh don hang',
        self::ACTIVITY_TRANSACTION_CREATE => 'Tao giao dich (thu/chi)',
    ];

    protected $table = 'approval_flows';

    protected $fillable = [
        'code',
        'name',
        'is_active',
        'applies_to',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'applies_to' => 'array',
    ];

    public static function availableActivities(): array
    {
        return self::ACTIVITY_LABELS;
    }

    public function appliesToActivity(string $activity): bool
    {
        $activities = (array) ($this->applies_to ?? []);

        if (empty($activities)) {
            return $activity === self::ACTIVITY_ORDER_CREATE;
        }

        return in_array($activity, $activities, true);
    }

    public function steps()
    {
        return $this->hasMany(ApprovalStep::class, 'approval_flow_id')->orderBy('step_order');
    }
}
