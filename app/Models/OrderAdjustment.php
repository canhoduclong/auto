<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAdjustment extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'order_id',
        'order_return_id',
        'requested_by',
        'approved_by',
        'rejected_by',
        'completed_by',
        'workflow_code',
        'status',
        'approval_note',
        'reject_reason',
        'adjustment_note',
        'order_changes',
        'fee_changes',
        'evidence_images',
        'return_warehouse_id',
        'warehouse_confirmation_status',
        'warehouse_confirmed_by',
        'warehouse_confirmed_at',
        'warehouse_confirmation_note',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'completed_at',
    ];

    protected $casts = [
        'fee_changes' => 'array',
        'order_changes' => 'array',
        'evidence_images' => 'array',
        'warehouse_confirmed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderReturn()
    {
        return $this->belongsTo(OrderReturn::class, 'order_return_id');
    }

    public function items()
    {
        return $this->hasMany(OrderAdjustmentItem::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function returnWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'return_warehouse_id');
    }

    public function warehouseConfirmer()
    {
        return $this->belongsTo(User::class, 'warehouse_confirmed_by');
    }

    public function approvalSteps()
    {
        return $this->hasMany(\App\Models\ApprovalOrder::class, 'order_adjustment_id');
    }

    public static function approvalRoleLabel(?string $roleSlug): string
    {
        $role = strtolower((string) $roleSlug);

        return match ($role) {
            'leader', 'leader_sale', 'sale_manager' => 'Leader',
            'manager', 'manager_sale', 'director' => 'Manager',
            'account', 'accountant', 'accounting' => 'Kế toán',
            'warehouse' => 'Kho',
            default => $role !== '' ? $role : 'bộ phận phụ trách',
        };
    }

    public function currentPendingApprovalStep(): ?\App\Models\ApprovalOrder
    {
        $approvals = $this->relationLoaded('approvalSteps')
            ? $this->approvalSteps
            : $this->approvalSteps()->with('step')->get();

        return $approvals
            ->where('status', 'pending')
            ->filter(fn ($approval) => $approval->step)
            ->sortBy(fn ($approval) => (int) ($approval->step?->step_order ?? PHP_INT_MAX))
            ->first();
    }

    public function progressLabel(): string
    {
        if ($this->status === self::STATUS_PENDING_APPROVAL) {
            $current = $this->currentPendingApprovalStep();

            return $current?->step
                ? 'Đang chờ '.self::approvalRoleLabel($current->step->role_slug).' duyệt'
                : 'Đang chờ duyệt';
        }

        return match ($this->status) {
            self::STATUS_DRAFT => 'Bản nháp, chưa gửi duyệt',
            self::STATUS_APPROVED => $this->warehouse_confirmation_status === 'pending'
                ? 'Đã duyệt, chờ Kho xác nhận'
                : 'Đã được duyệt',
            self::STATUS_REJECTED => 'Đã bị từ chối',
            self::STATUS_COMPLETED => 'Đã duyệt và hoàn tất',
            default => str_replace('_', ' ', (string) $this->status),
        };
    }

    public function progressTone(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED, self::STATUS_COMPLETED => 'success',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_DRAFT => 'secondary',
            default => 'warning',
        };
    }

    public function canBeDeletedBy(User $user): bool
    {
        if ((int) $this->requested_by !== (int) $user->id
            || ! in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING_APPROVAL], true)) {
            return false;
        }

        $approvals = $this->relationLoaded('approvalSteps')
            ? $this->approvalSteps
            : $this->approvalSteps()->get();

        // Các bước tự động bỏ qua không có approved_by nên sale vẫn có thể
        // xóa yêu cầu gửi trùng trước khi một người thực sự xử lý.
        return ! $approvals->contains(fn ($approval) => $approval->approved_by !== null);
    }

    public function requiresWarehouseConfirmation(): bool
    {
        if (! $this->relationLoaded('items')) {
            $this->load('items.orderItem');
        } else {
            $this->items
                ->filter(fn (OrderAdjustmentItem $item): bool => ! $item->relationLoaded('orderItem'))
                ->each->load('orderItem');
        }

        return $this->items->contains(function (OrderAdjustmentItem $item): bool {
            $orderItem = $item->orderItem;

            // Kho chỉ tham gia bước xác nhận cuối khi hàng thực tế thay đổi:
            // thay đổi số lượng, đổi loại hàng, hoặc thêm một loại hàng mới.
            // Thay đổi giá/cân nặng không làm phát sinh bước xác nhận Kho.
            return (int) $item->original_quantity !== (int) $item->adjusted_quantity
                || (! $orderItem && (int) $item->adjusted_quantity > 0)
                || ($orderItem && (int) $item->product_id !== (int) $orderItem->product_id)
                || ($orderItem && (int) $item->product_variant_id !== (int) $orderItem->product_variant_id);
        });
    }
}
