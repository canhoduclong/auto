<?php 
namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\ApprovalOrder;
use App\Models\ApprovalWorkflow;
use App\Models\Order;
use App\Models\OrderAdjustment;
use App\Models\Transaction;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB; 

class ApprovalService
{
    private function resolveActiveWorkflowForActivity(string $activity): ?ApprovalWorkflow
    {
        if ($activity === ApprovalWorkflow::ACTIVITY_ORDER_RETURN) {
            $workflow = ApprovalWorkflow::query()
                ->where('is_active', true)
                ->where('code', ApprovalWorkflow::ACTIVITY_ORDER_RETURN)
                ->with('steps')
                ->orderByDesc('id')
                ->first();

            if ($workflow) {
                return $workflow;
            }
        }

        return ApprovalWorkflow::query()
            ->where('is_active', true)
            ->where(function ($query) use ($activity): void {
                $query->whereJsonContains('applies_to', $activity)
                    ->orWhereNull('applies_to');
            })
            ->with('steps')
            ->orderByDesc('id')
            ->first();
    }

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

    public function initOrderApproval(Order $order, string $activity = ApprovalWorkflow::ACTIVITY_ORDER_CREATE): void
    {
        DB::transaction(function () use ($order, $activity): void {
            $workflow = $this->resolveActiveWorkflowForActivity($activity);

            if (!$workflow || $workflow->steps->isEmpty()) {
                $order->update(['status' => OrderStatus::Approved->value]);
                if ($order->shipper_id) {
                    app(ShipperAssignmentService::class)->publishDailySchedule(
                        (int) $order->shipper_id,
                        $order->delivery_date ?? now()->addDay(),
                    );
                }
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
                if ($order->shipper_id) {
                    app(ShipperAssignmentService::class)->publishDailySchedule(
                        (int) $order->shipper_id,
                        $order->delivery_date ?? now()->addDay(),
                    );
                }
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

            if ((bool) ($order->is_return_order ?? false)) {
                return;
            }

            if ($order->shipper_id) {
                app(ShipperAssignmentService::class)->publishDailySchedule(
                    (int) $order->shipper_id,
                    $order->delivery_date ?? now()->addDay(),
                    (int) $user->id,
                    'approval',
                );
            }

            if ($order->warehouse_id) {
                $warehouse = $order->warehouse;
                if ($warehouse) {
                    foreach ($warehouse->users as $wUser) {
                        if ($wUser->hasRole('warehouse') || $wUser->hasRole('package')) {
                            $wUser->notify(new \App\Notifications\WarehouseNewOrderApproved($order));
                        }
                    }
                }
            } else {
                $wUsers = \App\Models\User::whereHas('roles', function($q) { $q->whereIn('name', ['warehouse', 'package']); })->get();
                foreach ($wUsers as $wUser) {
                    $wUser->notify(new \App\Notifications\WarehouseNewOrderApproved($order));
                }
            }
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

    // ─── Order Adjustment Approval ───────────────────────────────────────────

    public function initAdjustmentApproval(OrderAdjustment $adjustment): bool
    {
        $workflow = $this->resolveActiveWorkflowForActivity(ApprovalWorkflow::ACTIVITY_ORDER_ADJUSTMENT_REQUEST);

        if (!$workflow || $workflow->steps->isEmpty()) {
            return false; // no workflow configured → caller handles fallback
        }

        $sortedSteps = $workflow->steps->sortBy('step_order')->values();

        DB::transaction(function () use ($adjustment, $sortedSteps): void {
            // Clear any old steps for this adjustment
            ApprovalOrder::where('order_adjustment_id', $adjustment->id)->delete();

            foreach ($sortedSteps as $step) {
                ApprovalOrder::create([
                    'order_id' => $adjustment->order_id,
                    'order_adjustment_id' => $adjustment->id,
                    'approval_step_id' => $step->id,
                    'status' => 'pending',
                ]);
            }
        });

        return true;
    }

    public function getCurrentPendingAdjustmentStep(OrderAdjustment $adjustment): ?ApprovalOrder
    {
        return ApprovalOrder::query()
            ->where('order_adjustment_id', $adjustment->id)
            ->where('status', 'pending')
            ->with('step')
            ->join('approval_steps as aps', 'aps.id', '=', 'approval_orders.approval_step_id')
            ->orderBy('aps.step_order')
            ->select('approval_orders.*')
            ->first();
    }

    public function canApproveAdjustmentStep(OrderAdjustment $adjustment, User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        $current = $this->getCurrentPendingAdjustmentStep($adjustment);

        if (!$current?->step) {
            return false;
        }

        $requiredRole = strtolower((string) $current->step->role_slug);
        return $user->roles->contains(fn ($role) => strtolower((string) $role->name) === $requiredRole);
    }

    public function approveAdjustmentStep(OrderAdjustment $adjustment, User $user, ?string $note = null): bool
    {
        $step = $this->getCurrentPendingAdjustmentStep($adjustment);

        if (!$step) {
            throw new Exception('Không tìm thấy bước duyệt đang chờ xử lý.');
        }

        if (!$this->canApproveAdjustmentStep($adjustment, $user)) {
            throw new Exception('Bạn không có quyền duyệt bước hiện tại.');
        }

        $step->update([
            'approved_by' => $user->id,
            'approved_at' => now(),
            'status'      => 'approved',
            'note'        => $note,
        ]);

        $hasPending = ApprovalOrder::where('order_adjustment_id', $adjustment->id)
            ->where('status', 'pending')
            ->exists();

        return !$hasPending; // returns true when all steps approved (adjustment can proceed)
    }

    public function rejectAdjustmentStep(OrderAdjustment $adjustment, User $user, ?string $note = null): void
    {
        $step = $this->getCurrentPendingAdjustmentStep($adjustment);

        if (!$step) {
            throw new Exception('Không tìm thấy bước duyệt đang chờ xử lý.');
        }

        if (!$this->canApproveAdjustmentStep($adjustment, $user)) {
            throw new Exception('Bạn không có quyền từ chối bước hiện tại.');
        }

        $step->update([
            'approved_by' => $user->id,
            'approved_at' => now(),
            'status' => 'rejected',
            'note' => $note,
        ]);

        // Cancel remaining pending steps
        ApprovalOrder::where('order_adjustment_id', $adjustment->id)
            ->where('status', 'pending')
            ->where('id', '!=', $step->id)
            ->update([
                'status' => 'rejected',
                'note' => 'Tự động kết thúc do yêu cầu đã bị từ chối ở bước trước.',
            ]);
    }

    // ─── Transaction Approval ─────────────────────────────────────────────────

    public function initTransactionApproval(Transaction $transaction): bool
    {
        $workflow = $this->resolveActiveWorkflowForActivity(ApprovalWorkflow::ACTIVITY_TRANSACTION_CREATE);

        if (!$workflow || $workflow->steps->isEmpty()) {
            return false;
        }

        $sortedSteps = $workflow->steps->sortBy('step_order')->values();

        DB::transaction(function () use ($transaction, $sortedSteps): void {
            ApprovalOrder::where('transaction_id', $transaction->id)->delete();

            foreach ($sortedSteps as $step) {
                ApprovalOrder::create([
                    'order_id' => $transaction->order_id ?: null,
                    'transaction_id' => $transaction->id,
                    'approval_step_id' => $step->id,
                    'status' => 'pending',
                ]);
            }
        });

        return true;
    }

    public function getCurrentPendingTransactionStep(Transaction $transaction): ?ApprovalOrder
    {
        return ApprovalOrder::query()
            ->where('transaction_id', $transaction->id)
            ->where('status', 'pending')
            ->with('step')
            ->join('approval_steps as aps', 'aps.id', '=', 'approval_orders.approval_step_id')
            ->orderBy('aps.step_order')
            ->select('approval_orders.*')
            ->first();
    }

    public function canApproveTransactionStep(Transaction $transaction, User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        $current = $this->getCurrentPendingTransactionStep($transaction);

        if (!$current?->step) {
            return false;
        }

        $requiredRole = strtolower((string) $current->step->role_slug);
        return $user->roles->contains(fn ($role) => strtolower((string) $role->name) === $requiredRole);
    }

    public function approveTransactionStep(Transaction $transaction, User $user, ?string $note = null): bool
    {
        $step = $this->getCurrentPendingTransactionStep($transaction);

        if (!$step) {
            throw new Exception('Không tìm thấy bước duyệt đang chờ xử lý.');
        }

        if (!$this->canApproveTransactionStep($transaction, $user)) {
            throw new Exception('Bạn không có quyền duyệt bước hiện tại.');
        }

        $step->update([
            'approved_by' => $user->id,
            'approved_at' => now(),
            'status'      => 'approved',
            'note'        => $note,
        ]);

        $hasPending = ApprovalOrder::where('transaction_id', $transaction->id)
            ->where('status', 'pending')
            ->exists();

        return !$hasPending;
    }

    public function rejectTransactionStep(Transaction $transaction, User $user, string $note): void
    {
        $step = $this->getCurrentPendingTransactionStep($transaction);

        if (!$step) {
            throw new Exception('Không tìm thấy bước duyệt đang chờ xử lý.');
        }

        if (!$this->canApproveTransactionStep($transaction, $user)) {
            throw new Exception('Bạn không có quyền từ chối bước hiện tại.');
        }

        $step->update([
            'approved_by' => $user->id,
            'approved_at' => now(),
            'status' => 'rejected',
            'note' => $note,
        ]);

        ApprovalOrder::where('transaction_id', $transaction->id)
            ->where('status', 'pending')
            ->where('id', '!=', $step->id)
            ->update([
                'status' => 'rejected',
                'note' => 'Tự động kết thúc do giao dịch đã bị từ chối ở bước trước.',
            ]);
    }

    // ══════════════════════════════════════════════════════════════════
    //  TASK ASSIGNMENT
    // ══════════════════════════════════════════════════════════════════

    /**
     * Create approval_orders rows for a task.
     * Returns false when no active workflow exists for task_assignment.
     */
    public function initTaskApproval(\App\Models\TaskAssignment $task): bool
    {
        $workflow = $this->resolveActiveWorkflowForActivity(
            ApprovalWorkflow::ACTIVITY_TASK_ASSIGNMENT
        );

        if (!$workflow || $workflow->steps->isEmpty()) {
            // No workflow — mark as in_progress immediately
            $task->update(['status' => 'in_progress']);
            return false;
        }

        if ($task->approval_flow_id === null) {
            $task->update(['approval_flow_id' => $workflow->id]);
        }

        foreach ($workflow->steps as $step) {
            ApprovalOrder::create([
                'task_id'          => $task->id,
                'approval_step_id' => $step->id,
                'status'           => 'pending',
            ]);
        }

        $task->update(['status' => 'in_progress']);
        return true;
    }

    public function getCurrentPendingTaskStep(\App\Models\TaskAssignment $task): ?ApprovalOrder
    {
        return ApprovalOrder::where('task_id', $task->id)
            ->where('status', 'pending')
            ->with('step')
            ->orderByRaw('(SELECT step_order FROM approval_steps WHERE id = approval_step_id)')
            ->first();
    }

    public function canApproveTaskStep(\App\Models\TaskAssignment $task, User $user): bool
    {
        $step = $this->getCurrentPendingTaskStep($task);
        if (!$step) return false;

        $roleSlug = $step->step?->role_slug;
        if (!$roleSlug) return false;

        return $user->hasRole($roleSlug) || $user->hasRole('admin');
    }

    /**
     * Approve the current pending step.
     * Returns true when all steps are done (task completed).
     */
    public function approveTaskStep(\App\Models\TaskAssignment $task, User $user, ?string $note = null): bool
    {
        $step = $this->getCurrentPendingTaskStep($task);

        if (!$step || !$this->canApproveTaskStep($task, $user)) {
            throw new Exception('Bạn không có quyền phê duyệt bước hiện tại.');
        }

        $step->update([
            'approved_by' => $user->id,
            'approved_at' => now(),
            'status'      => 'approved',
            'note'        => $note,
        ]);

        $remaining = ApprovalOrder::where('task_id', $task->id)->where('status', 'pending')->count();

        if ($remaining === 0) {
            $task->update([
                'status'       => \App\Models\TaskAssignment::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
            return true;
        }

        return false;
    }

    public function rejectTaskStep(\App\Models\TaskAssignment $task, User $user, string $note): void
    {
        $step = $this->getCurrentPendingTaskStep($task);

        if (!$step || !$this->canApproveTaskStep($task, $user)) {
            throw new Exception('Bạn không có quyền từ chối bước hiện tại.');
        }

        $step->update([
            'approved_by' => $user->id,
            'approved_at' => now(),
            'status'      => 'rejected',
            'note'        => $note,
        ]);

        ApprovalOrder::where('task_id', $task->id)
            ->where('status', 'pending')
            ->where('id', '!=', $step->id)
            ->update([
                'status' => 'rejected',
                'note'   => 'Tu dong ket thuc do cong viec bi tu choi o buoc truoc.',
            ]);

        $task->update([
            'status'        => \App\Models\TaskAssignment::STATUS_REJECTED,
            'reject_reason' => $note,
        ]);
    }
}
