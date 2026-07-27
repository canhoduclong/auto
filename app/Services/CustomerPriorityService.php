<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerCareLog;
use App\Models\CustomerOwnershipHistory;
use App\Models\CustomerPriority;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class CustomerPriorityService
{
    public const ACTION_SCORES = [
        'note' => 0,
        'quote_sent' => 10,
        'appointment_set' => 10,
        'meeting_done' => 10,
        'follow_up' => 0,
        'takeover' => 0,
        'free_customer' => 0,
        'order_closed' => 100,
        'score_added' => 0,
    ];

    public function ensureLifecycle(Customer $customer, ?int $defaultOwnerSaleId = null): void
    {
        $dirty = false;

        if (empty($customer->current_cycle_no) || (int) $customer->current_cycle_no < 1) {
            $customer->current_cycle_no = 1;
            $dirty = true;
        }

        if (empty($customer->customer_status)) {
            $customer->customer_status = 'active';
            $dirty = true;
        }

        if (!$customer->current_owner_sale_id && $customer->assigned_to) {
            $customer->current_owner_sale_id = $customer->assigned_to;
            $dirty = true;
        }

        if (!$customer->current_owner_sale_id && $defaultOwnerSaleId) {
            $customer->current_owner_sale_id = $defaultOwnerSaleId;
            $dirty = true;
        }

        if ($dirty) {
            $customer->save();
        }
    }

    public function attachSale(Customer $customer, int $saleId, ?int $preferredPriority = null, string $reason = 'duplicate_join'): CustomerPriority
    {
        return DB::transaction(function () use ($customer, $saleId, $preferredPriority, $reason) {
            $customer->refresh();
            $this->ensureLifecycle($customer, $saleId);

            $cycleNo = (int) $customer->current_cycle_no;

            $existing = CustomerPriority::query()
                ->where('customer_id', $customer->id)
                ->where('sale_id', $saleId)
                ->where('cycle_no', $cycleNo)
                ->first();

            $ownerPriority = CustomerPriority::query()
                ->where('customer_id', $customer->id)
                ->where('cycle_no', $cycleNo)
                ->where('is_active', true)
                ->where('priority_level', 1)
                ->first();

            $targetPriority = 1;
            if ($ownerPriority && (int) $ownerPriority->sale_id !== $saleId) {
                $targetPriority = in_array((int) $preferredPriority, [2, 3], true) ? (int) $preferredPriority : 3;
            }

            $priority = $existing ?: new CustomerPriority([
                'customer_id' => $customer->id,
                'sale_id' => $saleId,
                'cycle_no' => $cycleNo,
            ]);

            if (!$priority->exists) {
                $priority->care_score = 0;
                $priority->start_date = now();
            }

            $priorityDays = $this->priorityDays($targetPriority);
            $priority->priority_level = $targetPriority;
            $priority->is_active = true;
            $priority->takeover_eligible = false;
            $priority->last_activity_at = now();
            $priority->expire_date = $priorityDays > 0 ? now()->copy()->addDays($priorityDays) : null;
            $priority->save();

            if ($targetPriority === 1) {
                $this->setOwner($customer, $saleId, $reason);
            }

            $this->refreshRanking($customer);

            return $priority->fresh();
        });
    }

    public function addCareAction(
        Customer $customer,
        int $saleId,
        string $actionType,
        ?string $note = null,
        ?int $score = null,
        array $meta = []
    ): CustomerCareLog {
        return DB::transaction(function () use ($customer, $saleId, $actionType, $note, $score, $meta) {
            $customer->refresh();
            $this->ensureLifecycle($customer, $saleId);

            $cycleNo = (int) $customer->current_cycle_no;

            $priority = CustomerPriority::query()
                ->where('customer_id', $customer->id)
                ->where('sale_id', $saleId)
                ->where('cycle_no', $cycleNo)
                ->where('is_active', true)
                ->first();

            if (!$priority) {
                $priority = $this->attachSale($customer, $saleId, 3, 'duplicate_join');
            }

            if ($this->isScoringClosed($customer, $cycleNo) && in_array($actionType, ['quote_sent', 'appointment_set', 'meeting_done', 'order_closed'], true)) {
                $score = 0;
            }

            $earned = $score ?? (self::ACTION_SCORES[$actionType] ?? 0);

            $priority->care_score = max(0, (int) $priority->care_score + (int) $earned);
            $priority->last_activity_at = now();
            $priority->save();

            $log = CustomerCareLog::create([
                'customer_id' => $customer->id,
                'user_id' => $saleId,
                'note' => $note ?: $actionType,
                'action_type' => $actionType,
                'score_earned' => (int) $earned,
                'cycle_no' => $cycleNo,
                'meta' => empty($meta) ? null : $meta,
            ]);

            $this->refreshRanking($customer);

            return $log;
        });
    }

    public function takeover(Customer $customer, int $newSaleId, string $reason = 'takeover'): void
    {
        DB::transaction(function () use ($customer, $newSaleId, $reason) {
            $customer->refresh();
            $this->ensureLifecycle($customer, $newSaleId);

            CustomerPriority::query()
                ->where('customer_id', $customer->id)
                ->where('cycle_no', $customer->current_cycle_no)
                ->where('priority_level', 1)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'takeover_eligible' => true,
                ]);

            $this->attachSale($customer, $newSaleId, 1, $reason);
            $this->addCareAction($customer, $newSaleId, 'takeover', 'Takeover khách hàng', 0, ['reason' => $reason]);
        });
    }

    public function onOrderCreated(Customer $customer, Order $order, int $saleId): void
    {
        DB::transaction(function () use ($customer, $order, $saleId) {
            $this->assertCanCreateOrder($customer, $saleId);

            $customer->update([
                'customer_status' => 'ordered',
                'current_owner_sale_id' => $saleId,
                'assigned_to' => $saleId,
                'assigned_at' => now(),
            ]);

            $this->addCareAction($customer, $saleId, 'order_closed', 'Chốt đơn #' . $order->id, 100, ['order_id' => $order->id]);

            CustomerOwnershipHistory::query()
                ->where('customer_id', $customer->id)
                ->where('cycle_no', $customer->current_cycle_no)
                ->where('sale_id', $saleId)
                ->whereNull('ended_at')
                ->update([
                    'ended_at' => now(),
                    'transfer_reason' => 'ordered',
                    'order_id' => $order->id,
                    'final_score' => CustomerPriority::query()
                        ->where('customer_id', $customer->id)
                        ->where('sale_id', $saleId)
                        ->where('cycle_no', $customer->current_cycle_no)
                        ->value('care_score') ?? 0,
                ]);
        });
    }

    public function assertCanCreateOrder(Customer $customer, int $saleId): void
    {
        $this->ensureLifecycle($customer, $saleId);

        $owner = CustomerPriority::query()
            ->where('customer_id', $customer->id)
            ->where('cycle_no', $customer->current_cycle_no)
            ->where('priority_level', 1)
            ->where('is_active', true)
            ->first();

        if (!$owner || (int) $owner->sale_id !== (int) $saleId) {
            abort(403, 'Chỉ sale đang là Priority 1 mới được tạo đơn cho khách này.');
        }
    }

    public function applyFreeCustomerReset(Customer $customer): bool
    {
        $days = max((int) Setting::get('free_customer_days', Setting::get('customer_free_days', 0)), 0);
        if ($days <= 0) {
            return false;
        }

        $lastOrderAt = $customer->orders()->max('created_at');
        if (!$lastOrderAt || now()->diffInDays($lastOrderAt) < $days) {
            return false;
        }

        DB::transaction(function () use ($customer) {
            $customer->refresh();

            CustomerPriority::query()
                ->where('customer_id', $customer->id)
                ->where('cycle_no', $customer->current_cycle_no)
                ->where('is_active', true)
                ->update(['is_active' => false, 'takeover_eligible' => true]);

            CustomerOwnershipHistory::query()
                ->where('customer_id', $customer->id)
                ->where('cycle_no', $customer->current_cycle_no)
                ->whereNull('ended_at')
                ->update([
                    'ended_at' => now(),
                    'transfer_reason' => 'free_customer',
                ]);

            $nextCycle = (int) $customer->current_cycle_no + 1;
            $customer->update([
                'customer_status' => 'free',
                'free_from_date' => now(),
                'current_cycle_no' => $nextCycle,
                'current_owner_sale_id' => null,
                'assigned_to' => null,
                'assigned_at' => null,
            ]);

            CustomerCareLog::create([
                'customer_id' => $customer->id,
                'user_id' => null,
                'note' => 'Khách chuyển về trạng thái tự do do quá hạn không có đơn mới',
                'action_type' => 'free_customer',
                'score_earned' => 0,
                'cycle_no' => $nextCycle,
            ]);
        });

        return true;
    }

    public function refreshRanking(Customer $customer): void
    {
        $customer->refresh();

        $cycleNo = (int) $customer->current_cycle_no;
        $rows = CustomerPriority::query()
            ->where('customer_id', $customer->id)
            ->where('cycle_no', $cycleNo)
            ->where('is_active', true)
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $ownerSaleId = (int) ($customer->current_owner_sale_id ?: 0);

        $sorted = $rows->sort(function (CustomerPriority $a, CustomerPriority $b) use ($ownerSaleId) {
            $scoreCmp = ((int) $b->care_score) <=> ((int) $a->care_score);
            if ($scoreCmp !== 0) {
                return $scoreCmp;
            }

            $aTs = optional($a->last_activity_at)->timestamp ?? 0;
            $bTs = optional($b->last_activity_at)->timestamp ?? 0;
            $activityCmp = $bTs <=> $aTs;
            if ($activityCmp !== 0) {
                return $activityCmp;
            }

            $aOwner = (int) $a->sale_id === $ownerSaleId ? 1 : 0;
            $bOwner = (int) $b->sale_id === $ownerSaleId ? 1 : 0;
            return $bOwner <=> $aOwner;
        })->values();

        foreach ($sorted as $index => $row) {
            $newLevel = min($index + 1, 3);
            $priorityDays = $this->priorityDays($newLevel);
            $isExpired = $row->expire_date && $row->expire_date->isPast();

            $row->priority_level = $newLevel;
            $row->takeover_eligible = $newLevel === 1 && $isExpired;

            if (!$row->expire_date) {
                $row->expire_date = $priorityDays > 0
                    ? ($row->start_date ?: now())->copy()->addDays($priorityDays)
                    : null;
            }
            $row->save();
        }

        $currentOwner = $sorted->firstWhere('priority_level', 1);
        if ($currentOwner) {
            $this->setOwner($customer, (int) $currentOwner->sale_id, 'manager_override');
        }
    }

    private function setOwner(Customer $customer, int $saleId, string $reason): void
    {
        $customer->refresh();
        $cycleNo = (int) $customer->current_cycle_no;

        if ((int) $customer->current_owner_sale_id === $saleId) {
            $customer->update([
                'customer_status' => 'active',
                'free_from_date' => null,
                'assigned_to' => $saleId,
                'assigned_at' => now(),
            ]);

            return;
        }

        CustomerOwnershipHistory::query()
            ->where('customer_id', $customer->id)
            ->where('cycle_no', $cycleNo)
            ->whereNull('ended_at')
            ->update([
                'ended_at' => now(),
                'transfer_reason' => $reason,
            ]);

        CustomerOwnershipHistory::create([
            'customer_id' => $customer->id,
            'cycle_no' => $cycleNo,
            'sale_id' => $saleId,
            'priority_level' => 1,
            'started_at' => now(),
            'transfer_reason' => $reason,
        ]);

        $customer->update([
            'current_owner_sale_id' => $saleId,
            'customer_status' => 'active',
            'free_from_date' => null,
            'assigned_to' => $saleId,
            'assigned_at' => now(),
        ]);
    }

    private function isScoringClosed(Customer $customer, int $cycleNo): bool
    {
        return CustomerCareLog::query()
            ->where('customer_id', $customer->id)
            ->where('cycle_no', $cycleNo)
            ->where('action_type', 'order_closed')
            ->exists();
    }

    private function priorityDays(int $priority): int
    {
        $key = match ($priority) {
            1 => 'priority_1_days',
            2 => 'priority_2_days',
            default => 'priority_3_days',
        };

        return max((int) Setting::get($key, 0), 0);
    }
}
