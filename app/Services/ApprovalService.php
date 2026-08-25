<?php 
namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\ApprovalOrder;
use App\Models\ApprovalStep;
use App\Models\ApprovalWorkflow;
use App\Models\Order;
use App\Models\OrderAdjustment;
use App\Models\Transaction;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
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

    private function financeDepartmentHeadRoleSlugs(): array
    {
        return [
            'leader_sale',
            'leader',
            'sale_manager',
            'manager_sale',
            'manager',
            'manager_shipper',
            'procurement_manager',
            'warehouse',
            'package',
        ];
    }

    private function financeDirectorRoleSlugs(): array
    {
        return ['director'];
    }

    public function financeAccountingRoleSlugs(): array
    {
        return ['account', 'accountant', 'accounting'];
    }

    public function pendingAccountingAdjustments(): Collection
    {
        return $this->pendingAdjustmentsForRoles($this->financeAccountingRoleSlugs());
    }

    public function pendingWarehouseAdjustments(): Collection
    {
        return $this->pendingAdjustmentsForRoles(['warehouse'])
            ->filter(fn (OrderAdjustment $adjustment) => $adjustment->requiresWarehouseConfirmation())
            ->values();
    }

    /**
     * Yêu cầu điều chỉnh đang chờ đúng cấp duyệt bán hàng mà người dùng đang mở.
     */
    public function pendingSalesAdjustmentApprovals(User $user, ?string $activeRole = null): Collection
    {
        $activeRole = strtolower(trim((string) $activeRole));

        if (in_array($activeRole, $this->leaderRoleSlugs(), true)) {
            $teamId = (int) ($user->team_id ?? 0);
            if ($teamId <= 0) {
                return collect();
            }

            return $this->pendingAdjustmentsForRoles($this->leaderRoleSlugs(), $teamId);
        }

        if (in_array($activeRole, $this->managerRoleSlugs(), true)) {
            return $this->pendingAdjustmentsForRoles($this->managerRoleSlugs());
        }

        // Một số vai trò Sale/Leader dùng chung layout nên workspace có thể lưu
        // active_role = sale dù tài khoản thực tế có quyền duyệt Leader/Manager.
        $queues = collect();
        $teamId = (int) ($user->team_id ?? 0);
        if ($user->hasRole($this->leaderRoleSlugs()) && $teamId > 0) {
            $queues = $queues->concat(
                $this->pendingAdjustmentsForRoles($this->leaderRoleSlugs(), $teamId)
            );
        }
        if ($user->hasRole($this->managerRoleSlugs())) {
            $queues = $queues->concat(
                $this->pendingAdjustmentsForRoles($this->managerRoleSlugs())
            );
        }

        return $queues
            ->unique('id')
            ->sortByDesc(fn (OrderAdjustment $adjustment) => $adjustment->submitted_at?->timestamp ?? $adjustment->id)
            ->values();
    }

    public function warehouseAdjustmentQueue(): Collection
    {
        $waitingForConfirmation = OrderAdjustment::query()
            ->where('status', OrderAdjustment::STATUS_APPROVED)
            ->where('warehouse_confirmation_status', 'pending')
            ->with($this->adjustmentQueueRelations())
            ->latest('submitted_at')
            ->get()
            ->filter(fn (OrderAdjustment $adjustment) => $adjustment->requiresWarehouseConfirmation())
            ->values();

        return $this->pendingWarehouseAdjustments()
            ->concat($waitingForConfirmation)
            ->unique('id')
            ->sortByDesc(fn (OrderAdjustment $adjustment) => $adjustment->submitted_at?->timestamp ?? 0)
            ->values();
    }

    private function pendingAdjustmentsForRoles(array $roleSlugs, ?int $saleTeamId = null): Collection
    {
        $roleSlugs = array_values(array_unique(array_map('strtolower', $roleSlugs)));

        return OrderAdjustment::query()
            ->where('status', OrderAdjustment::STATUS_PENDING_APPROVAL)
            ->when($saleTeamId, fn ($query) => $query->whereHas(
                'order.user',
                fn ($userQuery) => $userQuery->where('team_id', $saleTeamId)
            ))
            ->whereHas('approvalSteps', function ($query) use ($roleSlugs): void {
                $query->where('status', 'pending')
                    ->whereHas('step', fn ($step) => $step->whereIn(DB::raw('LOWER(role_slug)'), $roleSlugs));
            })
            ->with($this->adjustmentQueueRelations())
            ->latest('submitted_at')
            ->get()
            ->filter(function (OrderAdjustment $adjustment) use ($roleSlugs): bool {
                $currentStep = $adjustment->approvalSteps
                    ->where('status', 'pending')
                    ->sortBy(fn (ApprovalOrder $approval) => $approval->step?->step_order ?? PHP_INT_MAX)
                    ->first();

                return $currentStep?->step
                    && in_array(strtolower((string) $currentStep->step->role_slug), $roleSlugs, true);
            })
            ->values();
    }

    private function adjustmentQueueRelations(): array
    {
        return [
            'order:id,code,customer_id,user_id,status,created_at,delivery_date,accounting_sales_import_batch_id',
            'order.customer:id,name',
            'order.user:id,name,short_name,team_id',
            'requester:id,name',
            'items.orderItem:id,product_id,product_variant_id,quantity,price',
            'items.variant.product',
            'approvalSteps.step:id,role_slug,step_order',
        ];
    }

    private function financeApprovalRoleSlugs(): array
    {
        return array_merge($this->financeAccountingRoleSlugs(), $this->financeDirectorRoleSlugs());
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

        app(OrderNotificationService::class)->notifySubmitted($order->fresh());
        if ($activity === ApprovalWorkflow::ACTIVITY_ORDER_CREATE) {
            app(OrderAutoApprovalService::class)->processOrder($order);
        }
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
        $acceptedRoles = match (true) {
            in_array($requiredRole, $this->leaderRoleSlugs(), true) => $this->leaderRoleSlugs(),
            in_array($requiredRole, $this->managerRoleSlugs(), true) => $this->managerRoleSlugs(),
            in_array($requiredRole, $this->financeAccountingRoleSlugs(), true) => $this->financeAccountingRoleSlugs(),
            default => [$requiredRole],
        };

        return $user->roles->contains(
            fn ($role) => in_array(strtolower((string) $role->name), $acceptedRoles, true)
        );
    }

    public function canApproveCurrentRole(User $user, string $requiredRole): bool
    {
        return $user->roles->contains(
            fn ($role) => strtolower((string) $role->name) === strtolower($requiredRole)
        );
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

            if ($user->hasRole($this->managerRoleSlugs())) {
                app(OrderNotificationService::class)->notifyApproved($order, $user);
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

            $this->skipUnneededWarehouseAdjustmentSteps($adjustment);
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
        $acceptedRoles = match (true) {
            in_array($requiredRole, $this->leaderRoleSlugs(), true) => $this->leaderRoleSlugs(),
            in_array($requiredRole, $this->managerRoleSlugs(), true) => $this->managerRoleSlugs(),
            in_array($requiredRole, $this->financeAccountingRoleSlugs(), true) => $this->financeAccountingRoleSlugs(),
            default => [$requiredRole],
        };

        return $user->roles->contains(
            fn ($role) => in_array(strtolower((string) $role->name), $acceptedRoles, true)
        );
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

        $this->skipUnneededWarehouseAdjustmentSteps($adjustment);

        $hasPending = ApprovalOrder::where('order_adjustment_id', $adjustment->id)
            ->where('status', 'pending')
            ->exists();

        return !$hasPending; // returns true when all steps approved (adjustment can proceed)
    }

    private function skipUnneededWarehouseAdjustmentSteps(OrderAdjustment $adjustment): int
    {
        if ($adjustment->requiresWarehouseConfirmation()) {
            return 0;
        }

        $skipped = ApprovalOrder::query()
            ->where('order_adjustment_id', $adjustment->id)
            ->where('status', 'pending')
            ->whereHas('step', fn ($query) => $query->whereRaw('LOWER(role_slug) = ?', ['warehouse']))
            ->update([
                'status' => 'approved',
                'approved_at' => now(),
                'note' => 'Tự động bỏ qua: yêu cầu không thay đổi số lượng hoặc loại hàng.',
            ]);

        $adjustment->update(['warehouse_confirmation_status' => 'not_required']);

        return $skipped;
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

        $sortedSteps = $this->resolveFinanceTransactionSteps($workflow);

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

    public function ensureTransactionApprovalFlow(Transaction $transaction): bool
    {
        $workflow = $this->resolveActiveWorkflowForActivity(ApprovalWorkflow::ACTIVITY_TRANSACTION_CREATE);

        if (!$workflow || $workflow->steps->isEmpty()) {
            return false;
        }

        $sortedSteps = $this->resolveFinanceTransactionSteps($workflow);

        DB::transaction(function () use ($transaction, $sortedSteps): void {
            ApprovalOrder::query()
                ->where('transaction_id', $transaction->id)
                ->where('status', 'pending')
                ->whereHas('step', function ($query): void {
                    $query->whereNotIn(DB::raw('LOWER(role_slug)'), $this->financeApprovalRoleSlugs());
                })
                ->delete();

            foreach ($sortedSteps as $step) {
                ApprovalOrder::firstOrCreate(
                    [
                        'transaction_id' => $transaction->id,
                        'approval_step_id' => $step->id,
                    ],
                    [
                        'order_id' => $transaction->order_id ?: null,
                        'status' => 'pending',
                    ]
                );
            }
        });

        return true;
    }

    private function resolveFinanceTransactionSteps(ApprovalWorkflow $workflow)
    {
        $workflow->loadMissing('steps');
        $sortedSteps = $this->ensureFinanceTransactionWorkflowSteps($workflow);

        $financeSteps = collect([
            $sortedSteps->first(fn ($step) => in_array(strtolower((string) $step->role_slug), $this->financeAccountingRoleSlugs(), true)),
            $sortedSteps->first(fn ($step) => in_array(strtolower((string) $step->role_slug), $this->financeDirectorRoleSlugs(), true)),
        ])->filter()->unique('id')->values();

        return $financeSteps->isNotEmpty()
            ? $financeSteps
            : $sortedSteps;
    }

    private function ensureFinanceTransactionWorkflowSteps(ApprovalWorkflow $workflow)
    {
        $steps = $workflow->steps->sortBy('step_order')->values();
        $maxOrder = (int) $steps->max('step_order');

        $requiredStages = [
            ['roles' => $this->financeAccountingRoleSlugs(), 'fallback' => 'accountant'],
            ['roles' => $this->financeDirectorRoleSlugs(), 'fallback' => 'Director'],
        ];

        foreach ($requiredStages as $stage) {
            $exists = $steps->contains(fn ($step) => in_array(strtolower((string) $step->role_slug), $stage['roles'], true));

            if ($exists) {
                continue;
            }

            $steps->push(ApprovalStep::create([
                'approval_flow_id' => $workflow->id,
                'step_order' => ++$maxOrder,
                'role_slug' => $stage['fallback'],
                'can_skip' => false,
            ]));
        }

        return $steps->sortBy('step_order')->values();
    }

    public function getCurrentPendingTransactionStep(Transaction $transaction): ?ApprovalOrder
    {
        $directorRoles = implode("','", $this->financeDirectorRoleSlugs());
        $accountingRoles = implode("','", $this->financeAccountingRoleSlugs());

        return ApprovalOrder::query()
            ->where('transaction_id', $transaction->id)
            ->where('status', 'pending')
            ->with('step')
            ->join('approval_steps as aps', 'aps.id', '=', 'approval_orders.approval_step_id')
            ->orderByRaw("
                CASE
                    WHEN LOWER(aps.role_slug) IN ('{$accountingRoles}') THEN 10
                    WHEN LOWER(aps.role_slug) IN ('{$directorRoles}') THEN 20
                    ELSE 90
                END
            ")
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
            ->whereHas('step', function ($query): void {
                $query->whereIn(DB::raw('LOWER(role_slug)'), $this->financeApprovalRoleSlugs());
            })
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
