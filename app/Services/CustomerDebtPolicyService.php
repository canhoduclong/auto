<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerDebtPolicyService
{
    private function prefix(): string
    {
        return 'customer_debt_policy_'.substr(hash('sha256', (string) config('services.google_sheets.debt_identity_spreadsheet_id', config('services.google_sheets.debt_spreadsheet_id'))), 0, 16).'_';
    }

    public function identity(array $group): string
    {
        return ! empty($group['customer_id']) ? 'customer_'.$group['customer_id'] : 'source_'.$group['key'];
    }

    public function policies(): array
    {
        $prefix = $this->prefix();
        $result = [];
        foreach (Setting::query()->where('key', 'like', $prefix.'%')->get(['key', 'value']) as $setting) {
            if (str_starts_with($setting->key, $prefix)) {
                $result[substr($setting->key, strlen($prefix))] = json_decode($setting->value, true) ?? [];
            }
        }

        return $result;
    }

    public static function cycleRules(): array
    {
        return [
            'payment_mode' => 'nullable|in:default,cycle',
            'settlement_method' => 'nullable|in:batch,bill,mixed',
            'cycle_type' => 'nullable|required_if:payment_mode,cycle|in:weekly,monthly',
            'cycle_close_weekday' => 'nullable|required_if:cycle_type,weekly|integer|min:1|max:7',
            'cycle_weekday' => 'nullable|required_if:cycle_type,weekly|integer|min:1|max:7',
            'cycle_close_monthday' => 'nullable|required_if:cycle_type,monthly|integer|min:1|max:31',
            'cycle_monthday' => 'nullable|required_if:cycle_type,monthly|integer|min:1|max:31',
        ];
    }

    public const SETTLEMENT_METHODS = ['batch' => 'Theo Đợt', 'bill' => 'Theo Bill', 'mixed' => 'Hỗn hợp'];

    public const PAYMENT_FORMS = self::SETTLEMENT_METHODS + ['cycle' => 'Theo chu kỳ'];

    public static function basePaymentForm(array $policy): string
    {
        return $policy['settlement_base_method'] ?? (($policy['payment_mode'] ?? null) === 'cycle' ? 'cycle' : $policy['settlement_method']);
    }

    public static function currentSettlementMethod(array $policy, ?string $asOf = null): string
    {
        $asOf ??= today()->toDateString();
        $changes = array_filter($policy['settlement_changes'] ?? [], fn ($date) => $date <= $asOf, ARRAY_FILTER_USE_KEY);

        return $changes[array_key_last($changes)] ?? self::basePaymentForm($policy);
    }

    public static function settlementPeriods(array $policy, ?string $initialDate = null): array
    {
        $changes = $policy['settlement_changes'] ?? [];
        $periods = [];
        $from = $initialDate;
        $method = self::basePaymentForm($policy);
        foreach ($changes as $date => $next) {
            if ($from === null || $from < $date) {
                $periods[] = ['from' => $from, 'to' => CarbonImmutable::parse($date)->subDay()->toDateString(), 'method' => $method];
            }
            $from = $date;
            $method = $next;
        }
        $periods[] = ['from' => $from, 'to' => null, 'method' => $method];

        return $periods;
    }

    public function defaults(): array
    {
        return ['allowed_orders' => 1, 'credit_limit' => 0, 'fixed_debt' => null, 'threshold_days' => 0, 'due_days' => 1, 'settlement_method' => 'batch', 'payment_mode' => 'default', 'cycle_type' => 'weekly', 'cycle_weekday' => 5, 'cycle_monthday' => 15];
    }

    public function isLocked(array $policy): bool
    {
        return array_key_exists('staff_edit_locked', $policy) ? (bool) $policy['staff_edit_locked'] : ! empty($policy['updated_at']);
    }

    private function applyDefaults(array $policy): array
    {
        $result = array_replace($this->defaults(), array_filter($policy, fn ($value) => $value !== null));
        $result['settlement_changes'] = $policy['settlement_changes'] ?? (empty($policy['bill_from']) ? [] : [$policy['bill_from'] => 'bill']);
        $result['warning_threshold'] = (int) $result['fixed_debt'] + (int) $result['credit_limit'];
        $result['staff_edit_locked'] = $this->isLocked($policy);
        $result['edit_version'] = (int) ($policy['edit_version'] ?? 0);

        return $result;
    }

    public function effective(array $group, array $policies): array
    {
        return $this->applyDefaults($policies[$this->identity($group)] ?? $policies['source_'.($group['key'] ?? '')] ?? []);
    }

    public function canEditCustomer(User $user, Customer $customer): bool
    {
        if ($user->canManageCustomerDebt()) {
            return true;
        }
        if (! $user->isSalesFlowRole()) {
            return false;
        }
        $owner = $customer->current_owner_sale_id ?? $customer->assigned_to ?? $customer->user_id;

        return (int) $owner === (int) $user->id || ($user->hasRole(['manager', 'manager_sale', 'sale_manager']) && $user->canViewAllMonitoringCustomers());
    }

    public function withWriteLock(array $group, callable $callback): mixed
    {
        $key = $this->prefix().$this->identity($group);

        return Cache::lock('customer-debt-policy-write-'.hash('sha256', $key), 15)->block(5, $callback);
    }

    private function change(array $group, callable $callback): void
    {
        $key = $this->prefix().$this->identity($group);
        $this->withWriteLock($group, function () use ($group, $key, $callback) {
            DB::transaction(function () use ($group, $key, $callback) {
                $lockedCustomer = ! empty($group['customer_id']) ? Customer::query()->whereKey($group['customer_id'])->lockForUpdate()->firstOrFail() : null;
                $setting = Setting::query()->where('key', $key)->lockForUpdate()->first();
                $current = $setting ? (json_decode($setting->value, true) ?? []) : [];
                $callback($key, $current, $lockedCustomer);
            }, 3);
        });
    }

    private function audit(string $identity, string $action, int $userId, array $before, array $after): void
    {
        Setting::set('customer_debt_policy_audit_'.Str::uuid(), json_encode([
            'identity' => $identity, 'action' => $action, 'actor_id' => $userId, 'at' => now()->toIso8601String(),
            'before' => $before, 'after' => $after,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public function save(array $group, array $data, int $userId, bool $canOverrideStaffLock = false, ?int $expectedVersion = null, ?User $editingUser = null): void
    {
        $this->change($group, function ($key, $current, $lockedCustomer) use ($group, $data, $userId, $canOverrideStaffLock, $expectedVersion, $editingUser) {
            if ($editingUser && ! $editingUser->canManageCustomerDebt()) {
                abort_unless($lockedCustomer && $this->canEditCustomer($editingUser, $lockedCustomer), 403);
            }
            if (! $canOverrideStaffLock && $this->isLocked($current)) {
                throw ValidationException::withMessages(['policy' => 'Tiêu chí đã khóa. Chỉ admin được mở khóa để cho phép sửa thêm một lần.']);
            }
            if ($expectedVersion !== null && $expectedVersion !== (int) ($current['edit_version'] ?? 0)) {
                throw ValidationException::withMessages(['policy_version' => 'Cấu hình đã thay đổi. Vui lòng tải lại trang trước khi cập nhật.']);
            }
            if ((! empty($current['bill_from']) || ! empty($current['settlement_changes'])) && isset($data['settlement_method']) && $data['settlement_method'] !== $current['settlement_method']) {
                throw ValidationException::withMessages(['settlement_method' => 'Khách đã có lịch sử chuyển hình thức thanh toán. Hãy thêm mốc chuyển trên trang đối chiếu.']);
            }
            $next = array_replace($current, [
                'allowed_orders' => array_key_exists('allowed_orders', $data) ? (isset($data['allowed_orders']) ? (int) $data['allowed_orders'] : null) : ($current['allowed_orders'] ?? null),
                'credit_limit' => isset($data['credit_limit']) ? (int) $data['credit_limit'] : null,
                'due_days' => array_key_exists('due_days', $data) ? (isset($data['due_days']) ? (int) $data['due_days'] : null) : ($current['due_days'] ?? null),
                'fixed_debt' => array_key_exists('fixed_debt', $data) ? (isset($data['fixed_debt']) ? (int) $data['fixed_debt'] : null) : ($current['fixed_debt'] ?? null),
                'threshold_days' => array_key_exists('threshold_days', $data) ? (isset($data['threshold_days']) ? (int) $data['threshold_days'] : null) : ($current['threshold_days'] ?? null),
                'settlement_method' => $data['settlement_method'] ?? $current['settlement_method'] ?? 'batch',
                'payment_mode' => $data['payment_mode'] ?? $current['payment_mode'] ?? 'default',
                'rolling_only' => $data['rolling_only'] ?? $current['rolling_only'] ?? false,
                'cycle_close_weekday' => isset($data['cycle_close_weekday']) ? (int) $data['cycle_close_weekday'] : ($current['cycle_close_weekday'] ?? null),
                'cycle_close_monthday' => isset($data['cycle_close_monthday']) ? (int) $data['cycle_close_monthday'] : ($current['cycle_close_monthday'] ?? null),
                'cycle_type' => $data['cycle_type'] ?? $current['cycle_type'] ?? 'weekly',
                'cycle_weekday' => (int) ($data['cycle_weekday'] ?? $current['cycle_weekday'] ?? 5),
                'cycle_monthday' => (int) ($data['cycle_monthday'] ?? $current['cycle_monthday'] ?? 15),
                'updated_by' => $userId, 'updated_at' => now()->toIso8601String(), 'staff_edit_locked' => true,
                'edit_version' => (int) ($current['edit_version'] ?? 0) + 1,
                'staff_edit_count' => (int) ($current['staff_edit_count'] ?? 0) + ($canOverrideStaffLock ? 0 : 1),
            ]);
            if (! empty($current['settlement_changes']) || ! empty($current['bill_from'])) {
                $next['settlement_base_method'] = self::basePaymentForm($current + $this->defaults());
            }
            Setting::set($key, json_encode($next, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $this->audit($this->identity($group), 'update', $userId, $current, $next);
        });
    }

    public function changeSettlementMethod(array $group, string $date, string $method, int $version, User $actor, ?string $end = null): void
    {
        abort_unless($actor->canManageCustomerDebt(), 403);
        $this->change($group, function ($key, $current) use ($group, $date, $method, $version, $actor, $end) {
            $policy = $this->applyDefaults($current + $group['policy']);
            $changes = $policy['settlement_changes'];
            $lastDate = array_key_last($changes);
            $returnDate = $policy['settlement_return_date'] ?? null;
            $previousMethod = $changes[$lastDate] ?? self::basePaymentForm($policy);
            $replacesReturn = $date === $returnDate && $returnDate === $lastDate;
            if ($version !== $policy['edit_version'] || ($lastDate !== null && $date <= $lastDate && ! $replacesReturn)
                || ($method === $previousMethod) || ($end !== null && $end < $date)) {
                throw ValidationException::withMessages(['transition_date' => 'Chọn hình thức khác và ngày bắt đầu sau giai đoạn đã cấu hình.']);
            }
            $changes[$date] = $method;
            $nextReturn = null;
            if ($end !== null) {
                $nextReturn = CarbonImmutable::parse($end)->addDay()->toDateString();
                $changes[$nextReturn] = $previousMethod;
            }
            $next = array_replace($policy, [
                'settlement_base_method' => self::basePaymentForm($policy),
                'settlement_changes' => $changes, 'settlement_return_date' => $nextReturn,
                'updated_by' => $actor->id, 'updated_at' => now()->toIso8601String(),
                'edit_version' => $version + 1,
            ]);
            unset($next['bill_from']);
            Setting::set($key, json_encode($next, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $this->audit($this->identity($group), 'change_settlement_method', $actor->id, $current, $next);
        });
    }

    public function unlock(array $group, User $actor, ?int $expectedVersion = null): void
    {
        abort_unless($actor->canManageCustomerDebt(), 403, 'Chỉ quản trị viên và kế toán được mở khóa tiêu chí công nợ.');
        $this->change($group, function ($key, $current) use ($group, $actor, $expectedVersion) {
            if (! $this->isLocked($current)) {
                return;
            }
            if ($expectedVersion !== null && $expectedVersion !== (int) ($current['edit_version'] ?? 0)) {
                throw ValidationException::withMessages(['policy_version' => 'Cấu hình đã thay đổi. Vui lòng tải lại trang trước khi mở khóa.']);
            }
            $next = array_replace($current, ['staff_edit_locked' => false, 'edit_version' => (int) ($current['edit_version'] ?? 0) + 1, 'unlocked_by' => $actor->id, 'unlocked_at' => now()->toIso8601String()]);
            Setting::set($key, json_encode($next, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $this->audit($this->identity($group), 'unlock', $actor->id, $current, $next);
        });
    }

    public function promoteSource(string $sourceKey, int $customerId): void
    {
        $policies = $this->policies();
        if (isset($policies['source_'.$sourceKey]) && ! isset($policies['customer_'.$customerId])) {
            Setting::set($this->prefix().'customer_'.$customerId, json_encode($policies['source_'.$sourceKey], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        }
    }

    /** Return the first scheduled close on or after the anchor, and its payment due date. */
    public function cycleDates(array $policy, CarbonImmutable $anchor): array
    {
        if ($policy['cycle_type'] === 'monthly') {
            $closeDay = (int) ($policy['cycle_close_monthday'] ?? $policy['cycle_monthday']);
            $month = $anchor->startOfMonth();
            $close = $month->addDays(min($closeDay, $month->daysInMonth) - 1);
            if ($close->lt($anchor)) {
                $month = $month->addMonth();
                $close = $month->addDays(min($closeDay, $month->daysInMonth) - 1);
            }
        } else {
            $closeDay = (int) ($policy['cycle_close_weekday'] ?? $policy['cycle_weekday']);
            $close = $anchor->addDays(($closeDay - $anchor->dayOfWeekIso + 7) % 7);
        }

        return [$close, $this->cycleDueDate($policy, $close)];
    }

    public function cycleDueDate(array $policy, CarbonImmutable $close): CarbonImmutable
    {
        if ($policy['cycle_type'] === 'monthly') {
            $day = (int) $policy['cycle_monthday'];
            $month = $close->startOfMonth();
            $due = $month->addDays(min($day, $month->daysInMonth) - 1);
            if ($due->lt($close)) {
                $month = $month->addMonth();
                $due = $month->addDays(min($day, $month->daysInMonth) - 1);
            }

            return $due;
        }

        return $close->addDays(((int) $policy['cycle_weekday'] - $close->dayOfWeekIso + 7) % 7);
    }

    /** Allocate receipts to the oldest sales. All lines for a customer/day form one sale. */
    public function evaluate(array $entries, array $policy, ?string $asOf = null): array
    {
        $policy = $this->applyDefaults($policy);
        $today = CarbonImmutable::parse($asOf ?? now()->toDateString())->startOfDay();
        $buckets = [];
        $credits = 0;
        $balance = 0;
        $latestDelivery = null;
        $usesFixedDebt = $policy['fixed_debt'] !== null;
        $dailyChanges = [];
        foreach ($entries as $entry) {
            if ($entry['date'] > $today->toDateString()) {
                continue;
            }
            if ($usesFixedDebt) {
                $change = $entry['amount'] - ($entry['gift'] ?? 0);
                $dailyChanges[$entry['date']] = ($dailyChanges[$entry['date']] ?? 0)
                    + ($entry['type'] === 'payment' ? -$entry['amount'] : $change);
            }
            if ($entry['type'] === 'adjustment') {
                $balance += $entry['amount'];
                if ($entry['amount'] < 0) {
                    $credits -= $entry['amount'];
                } elseif ($entry['amount'] > 0) {
                    $buckets['adjustment_'.($entry['adjustment_id'] ?? $entry['row'])] = ['date' => $entry['date'], 'amount' => $entry['amount'], 'is_bill' => false];
                }

                continue;
            }
            if ($entry['type'] === 'payment') {
                $credits += $entry['amount'];
                $balance -= $entry['amount'];

                continue;
            }
            if ($entry['amount'] > 0) {
                $latestDelivery = max($latestDelivery ?? '', $entry['date']);
            }
            $net = $entry['amount'] - ($entry['gift'] ?? 0);
            $balance += $net;
            if ($net <= 0) {
                $credits -= $net;

                continue;
            }
            $key = $entry['date'];
            $buckets[$key] ??= ['date' => $entry['date'], 'amount' => 0, 'is_bill' => true];
            $buckets[$key]['amount'] += $net;
        }
        uasort($buckets, fn ($a, $b) => $a['date'] <=> $b['date']);
        $unpaid = [];
        foreach ($buckets as $bucket) {
            $paid = min(max(0, $credits), $bucket['amount']);
            $credits -= $paid;
            $bucket['amount'] -= $paid;
            if ($bucket['amount'] > 0) {
                $unpaid[] = $bucket;
            }
        }
        $balance = max(0, $balance);
        $oldest = $balance > 0 ? ($unpaid[0]['date'] ?? null) : null;
        $age = $oldest ? (int) CarbonImmutable::parse($oldest)->diffInDays($today) : 0;
        $count = $balance > 0 ? count(array_filter($unpaid, fn ($bucket) => $bucket['is_bill'])) : 0;
        $dueDays = $policy['due_days'] ?? null;
        $dueDate = $balance > 0 && ($latestDelivery ?? $oldest) !== null && $dueDays !== null ? CarbonImmutable::parse($latestDelivery ?? $oldest)->addDays((int) $dueDays)->toDateString() : null;
        $cycleCloseDate = null;
        $usesCycle = self::currentSettlementMethod($policy, $today->toDateString()) === 'cycle';
        if ($usesCycle) {
            // A new delivery must not postpone an existing unpaid cycle.
            $dueDate = null;
            if ($balance > 0 && $oldest) {
                [$close, $due] = $this->cycleDates($policy, CarbonImmutable::parse($oldest));
                $cycleCloseDate = $close->toDateString();
                $dueDate = $due->toDateString();
            }
        }
        $rolling = ! $usesCycle && ($policy['rolling_only'] ?? false);
        if ($rolling) {
            $dueDate = null;
        }
        $lateDays = $dueDate !== null ? max(0, (int) CarbonImmutable::parse($dueDate)->diffInDays($today, false)) : 0;
        $orderLimit = $policy['allowed_orders'] ?? null;
        $creditLimit = $policy['credit_limit'] ?? null;
        $orderReached = ! $usesFixedDebt && $balance > 0 && $orderLimit !== null && ($rolling ? $count > 1 : ($policy['payment_mode'] !== 'cycle' && $count >= (int) $orderLimit));
        $creditReached = $balance > 0 && $creditLimit !== null && (($rolling || $policy['payment_mode'] === 'cycle') ? ((int) $creditLimit > 0 && $balance >= (int) $creditLimit) : $balance >= (int) $creditLimit);
        if ($usesFixedDebt) {
            $creditReached = $balance > 0 && $balance >= $policy['warning_threshold'];
        }

        $aboveThreshold = $creditReached && $balance > $policy['warning_threshold'];
        $thresholdAge = null;
        if ($usesFixedDebt && $aboveThreshold) {
            ksort($dailyChanges);
            $runningBalance = 0;
            $aboveSince = null;
            foreach ($dailyChanges as $date => $change) {
                $runningBalance += $change;
                $aboveSince = $runningBalance > $policy['warning_threshold'] ? ($aboveSince ?? $date) : null;
            }
            if ($aboveSince !== null) {
                $thresholdAge = (int) CarbonImmutable::parse($aboveSince)->diffInDays($today);
            }
        }
        $dueSoon = $balance > 0 && $dueDate !== null && (int) $today->diffInDays(CarbonImmutable::parse($dueDate), false) <= 3;
        $risk = $balance <= 0 || $age < 15 ? null : ($age <= 30 ? 'medium' : ($age <= 60 ? 'high' : 'very_high'));
        $reasons = [];
        if ($orderReached) {
            $reasons[] = $count > (int) $orderLimit ? 'Vượt số đơn được phép nợ' : 'Đã chạm số đơn được phép nợ';
        }
        if ($creditReached) {
            $reasons[] = $usesFixedDebt
                ? ($aboveThreshold ? 'Vượt ngưỡng cảnh báo công nợ' : 'Đã chạm ngưỡng cảnh báo công nợ')
                : ($aboveThreshold ? 'Vượt trần công nợ' : 'Đã chạm trần công nợ');
        }
        if ($lateDays > 0) {
            $reasons[] = 'Trễ thanh toán '.$lateDays.' ngày';
        } elseif ($dueSoon) {
            $reasons[] = $dueDate === $today->toDateString() ? 'Đến hạn thanh toán hôm nay' : 'Sắp đến hạn thanh toán';
        }
        if ($risk !== null) {
            $reasons[] = 'Khoản còn nợ lâu nhất '.$age.' ngày';
        }

        return ['balance' => $balance, 'unpaid_orders' => $count, 'oldest_date' => $oldest, 'age_days' => $age, 'due_date' => $dueDate, 'cycle_close_date' => $cycleCloseDate, 'latest_delivery_date' => $latestDelivery, 'overdue_days' => $lateDays, 'overdue' => $lateDays > 0, 'threshold_reached' => $creditReached, 'above_threshold' => $aboveThreshold, 'threshold_age_days' => $thresholdAge, 'threshold_grace_elapsed' => $thresholdAge !== null && $thresholdAge >= $policy['threshold_days'], 'remind' => $orderReached || $creditReached || $dueSoon || $risk !== null, 'risk' => $risk, 'reasons' => $reasons];
    }
}
