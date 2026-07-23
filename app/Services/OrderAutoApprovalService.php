<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderAdjustment;
use App\Models\OrderAutoApprovalRule;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class OrderAutoApprovalService
{
    public function __construct(private ApprovalService $approvalService)
    {
    }

    public function processOrder(Order $order): int
    {
        $approvedSteps = 0;

        for ($iteration = 0; $iteration < 5; $iteration++) {
            $order->refresh();
            $currentStep = $this->approvalService->getCurrentPendingStep($order);
            if (!$currentStep?->step) {
                break;
            }

            $rule = $this->findRuleForStep(
                OrderAutoApprovalRule::TYPE_NEW_ORDER,
                (string) $currentStep->step->role_slug,
                $order
            );

            if (!$rule || !$this->orderMatchesRule($order, $rule)) {
                break;
            }

            try {
                $this->approvalService->approve(
                    $order,
                    $rule->user,
                    $this->approvalNote($rule)
                );
                $approvedSteps++;
            } catch (\Throwable $exception) {
                Log::warning('Không thể tự động duyệt đơn.', [
                    'order_id' => $order->id,
                    'rule_id' => $rule->id,
                    'message' => $exception->getMessage(),
                ]);
                break;
            }
        }

        return $approvedSteps;
    }

    /**
     * @return array{approved_steps:int, all_approved:bool, approver:?User}
     */
    public function processAdjustment(OrderAdjustment $adjustment): array
    {
        $approvedSteps = 0;
        $lastApprover = null;

        for ($iteration = 0; $iteration < 5; $iteration++) {
            $adjustment->refresh();
            $currentStep = $this->approvalService->getCurrentPendingAdjustmentStep($adjustment);
            if (!$currentStep?->step) {
                break;
            }

            $rule = $this->findRuleForStep(
                OrderAutoApprovalRule::TYPE_ORDER_ADJUSTMENT,
                (string) $currentStep->step->role_slug,
                $adjustment->order
            );

            if (!$rule || !$this->adjustmentMatchesRule($adjustment, $rule)) {
                break;
            }

            try {
                $this->approvalService->approveAdjustmentStep(
                    $adjustment,
                    $rule->user,
                    $this->approvalNote($rule)
                );
                $lastApprover = $rule->user;
                $approvedSteps++;
            } catch (\Throwable $exception) {
                Log::warning('Không thể tự động duyệt điều chỉnh đơn.', [
                    'order_adjustment_id' => $adjustment->id,
                    'rule_id' => $rule->id,
                    'message' => $exception->getMessage(),
                ]);
                break;
            }
        }

        return [
            'approved_steps' => $approvedSteps,
            'all_approved' => $approvedSteps > 0 && !$adjustment->approvalSteps()->where('status', 'pending')->exists(),
            'approver' => $lastApprover,
        ];
    }

    public function processPendingForUser(User $user): array
    {
        $orderSteps = 0;
        $adjustmentSteps = 0;
        $completedAdjustments = [];

        Order::query()
            ->whereIn('status', ['pending_leader_approval', 'pending_manager_approval', 'pending'])
            ->where(function ($query): void {
                $query->whereNull('is_return_order')->orWhere('is_return_order', false);
            })
            ->where(function ($query): void {
                $query->whereNull('order_type')->orWhere('order_type', '!=', 'order_return');
            })
            ->whereHas('approvals', fn ($query) => $query->where('status', 'pending'))
            ->with(['user', 'items.variant.latestPriceRule'])
            ->orderBy('id')
            ->limit(500)
            ->get()
            ->each(function (Order $order) use (&$orderSteps): void {
                $orderSteps += $this->processOrder($order);
            });

        OrderAdjustment::query()
            ->where('status', OrderAdjustment::STATUS_PENDING_APPROVAL)
            ->whereHas('approvalSteps', fn ($query) => $query->where('status', 'pending'))
            ->with(['order.user', 'items.variant.latestPriceRule'])
            ->orderBy('id')
            ->limit(500)
            ->get()
            ->each(function (OrderAdjustment $adjustment) use (&$adjustmentSteps, &$completedAdjustments): void {
                $result = $this->processAdjustment($adjustment);
                $adjustmentSteps += $result['approved_steps'];
                if ($result['all_approved'] && $result['approver']) {
                    $completedAdjustments[] = [$adjustment, $result['approver']];
                }
            });

        return compact('orderSteps', 'adjustmentSteps', 'completedAdjustments');
    }

    private function findRuleForStep(string $type, string $roleSlug, ?Order $order): ?OrderAutoApprovalRule
    {
        if (!$order) {
            return null;
        }

        $normalizedRole = strtolower(trim($roleSlug));

        return OrderAutoApprovalRule::query()
            ->where('order_type', $type)
            ->where('enabled', true)
            ->whereHas('user.roles', fn ($query) => $query->whereRaw('LOWER(name) = ?', [$normalizedRole]))
            ->with('user.roles')
            ->orderBy('id')
            ->get()
            ->first(function (OrderAutoApprovalRule $rule) use ($order, $normalizedRole): bool {
                if (!$rule->user || !$this->approvalService->canApproveCurrentRole($rule->user, $normalizedRole)) {
                    return false;
                }

                if (!in_array($normalizedRole, ['leader', 'leader_sale', 'sale_manager'], true)) {
                    return true;
                }

                $leaderTeamId = (int) ($rule->user->team_id ?? 0);
                return $leaderTeamId > 0 && $leaderTeamId === (int) ($order->user?->team_id ?? 0);
            });
    }

    private function orderMatchesRule(Order $order, OrderAutoApprovalRule $rule): bool
    {
        $order->loadMissing('items.variant.latestPriceRule');

        return $this->itemsMatchRule(
            $order->items,
            $rule,
            fn ($item): int => (int) ($item->quantity ?? 0),
            fn ($item): float => (float) ($item->price ?? 0)
        );
    }

    private function adjustmentMatchesRule(OrderAdjustment $adjustment, OrderAutoApprovalRule $rule): bool
    {
        $adjustment->loadMissing('items.variant.latestPriceRule');

        return $this->itemsMatchRule(
            $adjustment->items,
            $rule,
            fn ($item): int => (int) ($item->adjusted_quantity ?? 0),
            fn ($item): float => (float) ($item->adjusted_price ?? 0)
        );
    }

    private function itemsMatchRule($items, OrderAutoApprovalRule $rule, callable $quantity, callable $price): bool
    {
        if (!$rule->require_min_price || $items->isEmpty()) {
            return !$items->isEmpty();
        }

        $totalQuantity = (int) $items->sum($quantity);
        $bulkAllowance = $rule->allow_bulk_below_min
            && $totalQuantity >= max(1, (int) $rule->bulk_min_quantity)
            ? max(0, (float) $rule->bulk_below_min_amount)
            : 0;

        return $items->every(function ($item) use ($price, $bulkAllowance): bool {
            $priceRule = $item->variant?->latestPriceRule;
            if (!$priceRule) {
                return false;
            }

            $minPrice = max(0, (float) $priceRule->min_price);
            $lowestAllowedPrice = max(0, $minPrice - $bulkAllowance);

            return $price($item) >= $lowestAllowedPrice;
        });
    }

    private function approvalNote(OrderAutoApprovalRule $rule): string
    {
        $note = 'Tự động duyệt: giá bán không thấp hơn giá Min.';
        if ($rule->allow_bulk_below_min) {
            $note .= ' Sản lượng từ ' . number_format($rule->bulk_min_quantity)
                . ' được thấp hơn giá Min tối đa '
                . number_format((float) $rule->bulk_below_min_amount, 0, ',', '.') . 'đ.';
        }

        return $note;
    }
}
