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
    private function leaderRoleSlugs(): array
    {
        return ['leader_sale', 'leader', 'sale_manager'];
    }

    private function managerRoleSlugs(): array
    {
        return ['manager_sale', 'manager', 'director'];
    }

    private function approverRoleSlugs(): array
    {
        return array_merge($this->leaderRoleSlugs(), $this->managerRoleSlugs());
    }

    private function isApproverRole(?string $roleSlug): bool
    {
        return in_array(strtolower((string) $roleSlug), $this->approverRoleSlugs(), true);
    }

    private function mapPendingStatusByRole(?string $roleSlug): string
    {
        $role = strtolower((string) $roleSlug);

        return match (true) {
            in_array($role, $this->leaderRoleSlugs(), true) => 'pending_leader_approval',
            in_array($role, $this->managerRoleSlugs(), true) => 'pending_manager_approval',
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

            $sortedSteps = $workflow->steps
                ->sortBy('step_order')
                ->values();

            // Force approval flow into 2 business stages: leader -> manager.
            $leaderStep = $sortedSteps->first(fn ($step) => in_array(strtolower((string) $step->role_slug), $this->leaderRoleSlugs(), true));
            $managerStep = $sortedSteps->first(fn ($step) => in_array(strtolower((string) $step->role_slug), $this->managerRoleSlugs(), true));

            $approverSteps = collect([$leaderStep, $managerStep])->filter()->values();

            if ($approverSteps->isEmpty()) {
                $order->update(['status' => OrderStatus::Approved->value]);
                return;
            }

            foreach ($approverSteps as $step) {
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

            $firstStep = $approverSteps->first();
            $order->update(['status' => $this->mapPendingStatusByRole($firstStep?->role_slug)]);
        });
    }

    public function getCurrentPendingStep(Order $order): ?ApprovalOrder
    {
        return ApprovalOrder::query()
            ->where('order_id', $order->id)
            ->with('step')
            ->where('status', 'pending')
            ->whereHas('step', function ($q) {
                $q->whereIn(DB::raw('LOWER(role_slug)'), $this->approverRoleSlugs());
            })
            ->join('approval_steps as aps', 'aps.id', '=', 'approval_orders.approval_step_id')
            ->whereIn(DB::raw('LOWER(aps.role_slug)'), $this->approverRoleSlugs())
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

        $hasPending = $order->approvals()
            ->where('status', 'pending')
            ->whereHas('step', function ($q) {
                $q->whereIn(DB::raw('LOWER(role_slug)'), $this->approverRoleSlugs());
            })
            ->exists();
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
            ->whereHas('step', function ($q) {
                $q->whereIn(DB::raw('LOWER(role_slug)'), $this->approverRoleSlugs());
            })
            ->update([
                'status' => 'rejected',
                'note' => 'Tự động kết thúc do đơn đã bị từ chối ở bước trước.',
            ]);

        $order->update(['status' => OrderStatus::Rejected->value]);
    }
}