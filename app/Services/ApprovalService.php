<?php 
namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\ApprovalOrder;
use App\Models\ApprovalWorkflow;
use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB; 

class ApprovalService
{
    private function mapPendingStatusByRole(?string $roleSlug): string
    {
        $role = strtolower((string) $roleSlug);

        return match (true) {
            in_array($role, ['leader_sale', 'leader', 'sale_manager'], true) => 'pending_leader_approval',
            in_array($role, ['manager_sale', 'manager', 'director'], true) => 'pending_manager_approval',
            in_array($role, ['warehouse', 'kho'], true) => 'pending_warehouse_approval',
            in_array($role, ['shipper', 'giao_hang'], true) => 'pending_shipper_approval',
            default => OrderStatus::Pending->value,
        };
    }

    public function initOrderApproval(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $workflow = ApprovalWorkflow::query()
                ->where('is_active', true)
                ->with('steps')
                ->orderByDesc('id')
                ->first();

            if (!$workflow || $workflow->steps->isEmpty()) {
                $order->update(['status' => OrderStatus::Approved->value]);
                return;
            }

            foreach ($workflow->steps as $step) {
                ApprovalOrder::updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'approval_step_id' => $step->id,
                    ],
                    [
                        'status' => 'pending',
                    ]
                );
            }

            $firstStep = $workflow->steps->sortBy('step_order')->first();
            $order->update(['status' => $this->mapPendingStatusByRole($firstStep?->role_slug)]);
        });
    }

    public function getCurrentPendingStep(Order $order): ?ApprovalOrder
    {
        return ApprovalOrder::query()
            ->where('order_id', $order->id)
            ->with('step')
            ->where('status', 'pending')
            ->whereHas('step')
            ->join('approval_steps as aps', 'aps.id', '=', 'approval_orders.approval_step_id')
            ->orderBy('aps.step_order')
            ->select('approval_orders.*')
            ->first();
    }

    public function canApproveCurrentStep(Order $order, User $user): bool
    {
        $current = $this->getCurrentPendingStep($order);

        if (!$current?->step) {
            return false;
        }

        $requiredRole = strtolower((string) $current->step->role_slug);
        return $user->roles->contains(fn ($role) => strtolower((string) $role->name) === $requiredRole);
    }

    public function approve(Order $order, User $user, ?string $note = null): void
    {
        $step = $this->getCurrentPendingStep($order);

        if (!$step) {
            throw new Exception('Không tìm thấy bước duyệt đang chờ xử lý.');
        }

        if (!$this->canApproveCurrentStep($order, $user)) {
            throw new Exception('Bạn không có quyền duyệt bước hiện tại.');
        }

        $step->update([
            'approved_by' => $user->id,
            'approved_at' => now(),
            'status'      => 'approved',
            'note'        => $note,
        ]);

        $hasPending = $order->approvals()->where('status', 'pending')->exists();
        if (!$hasPending) {
            $order->update(['status' => OrderStatus::Approved->value]);
            return;
        }

        $nextPending = $this->getCurrentPendingStep($order);
        $order->update(['status' => $this->mapPendingStatusByRole($nextPending?->step?->role_slug)]);
    }

    public function reject(Order $order, User $user, ?string $note = null): void
    {
        $step = $this->getCurrentPendingStep($order);

        if (!$step) {
            throw new Exception('Không tìm thấy bước duyệt đang chờ xử lý.');
        }

        if (!$this->canApproveCurrentStep($order, $user)) {
            throw new Exception('Bạn không có quyền từ chối bước hiện tại.');
        }

        $step->update([
            'approved_by' => $user->id,
            'approved_at' => now(),
            'status' => 'rejected',
            'note' => $note,
        ]);

        $order->approvals()
            ->where('status', 'pending')
            ->where('id', '!=', $step->id)
            ->update([
                'status' => 'rejected',
                'note' => 'Tự động kết thúc do đơn đã bị từ chối ở bước trước.',
            ]);

        $order->update(['status' => OrderStatus::Rejected->value]);
    }
}