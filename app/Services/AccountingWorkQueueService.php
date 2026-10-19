<?php

namespace App\Services;

use App\Models\{AccountingReconciliation, OperatingProposal, Order, TaskAssignment, Transaction, User};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AccountingWorkQueueService
{
    public function revenueOrders(): Builder
    {
        return Order::query()->whereIn('status', ['delivered', 'completed'])->whereNull('trash_at')
            ->whereDoesntHave('accountingReconciliation', fn ($q) => $q->where('status', AccountingReconciliation::STATUS_CONFIRMED))
            ->when(Schema::hasTable('accounting_reconciliation_exclusions'), fn ($q) => $q->whereNotExists(fn ($e) => $e->selectRaw('1')
                ->from('accounting_reconciliation_exclusions')->whereColumn('accounting_reconciliation_exclusions.order_id', 'orders.id')));
    }

    public function financeRequests(User $user): Collection
    {
        $approval = app(ApprovalService::class);
        $canComplete = $user->hasRole('admin') || collect(['account', 'accountant', 'accounting'])->contains(fn ($role) => $user->hasRole($role));

        return Transaction::query()->whereNotNull('request_source')
            ->whereIn('status', [Transaction::STATUS_PENDING_APPROVAL, Transaction::STATUS_APPROVED_PENDING_COMPLETION])
            ->with(['submitter:id,name', 'approvalSteps.step'])->latest('created_at')->get()
            ->filter(fn ($transaction) => $transaction->status === Transaction::STATUS_APPROVED_PENDING_COMPLETION
                ? $canComplete : $approval->canApproveTransactionStep($transaction, $user))->values();
    }

    public function todoTasks(User $user): Builder
    {
        return TaskAssignment::query()->whereIn('status', ['pending', 'processing', 'in_progress', 'rejected'])
            ->whereHas('assignees', fn ($q) => $q->where('user_id', $user->id)->whereIn('status', ['pending', 'processing', 'in_progress', 'rejected']));
    }

    public function relatedTasks(User $user): Builder
    {
        return TaskAssignment::query()->where('created_by', $user->id)->where('status', 'completed');
    }

    public function relatedProposals(User $user): Builder
    {
        return OperatingProposal::query()->where('status', 'open')->where('closes_at', '>', now())
            ->whereHas('votes', fn ($q) => $q->where('user_id', $user->id)->whereNull('choice'));
    }

    public function dashboard(User $user): array
    {
        $revenue = $this->revenueOrders();
        $adjustments = app(ApprovalService::class)->pendingAccountingAdjustments();
        $finance = $this->financeRequests($user);
        $todo = $this->todoTasks($user);
        $verification = $this->relatedTasks($user);
        $votes = $this->relatedProposals($user);
        return [
            'revenue' => ['count' => (clone $revenue)->count(), 'items' => (clone $revenue)->with('customer:id,name')->oldest('delivered_at')->limit(3)->get()],
            'adjustments' => ['count' => $adjustments->count(), 'items' => $adjustments->take(3)],
            'finance' => ['count' => $finance->count(), 'items' => $finance->take(3)],
            'todo' => ['count' => (clone $todo)->count(), 'items' => (clone $todo)->orderByRaw('due_date IS NULL')->orderBy('due_date')->limit(3)->get()],
            'related' => ['count' => (clone $verification)->count() + (clone $votes)->count(),
                'tasks' => (clone $verification)->oldest('updated_at')->limit(2)->get(),
                'votes' => (clone $votes)->orderBy('closes_at')->limit(2)->get()],
        ];
    }
}
