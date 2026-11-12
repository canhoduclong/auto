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
        return 'customer_debt_policy_'.substr(hash('sha256', (string) config('services.google_sheets.debt_spreadsheet_id')), 0, 16).'_';
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

    public function defaults(): array
    {
        return ['allowed_orders' => 0, 'credit_limit' => 0, 'due_days' => 1];
    }

    public function isLocked(array $policy): bool
    {
        return array_key_exists('staff_edit_locked', $policy) ? (bool) $policy['staff_edit_locked'] : ! empty($policy['updated_at']);
    }

    private function applyDefaults(array $policy): array
    {
        $result = array_replace($this->defaults(), array_filter($policy, fn ($value) => $value !== null));
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
        if ($user->isAdmin()) {
            return true;
        }
        if (! $user->isSalesFlowRole()) {
            return false;
        }
        $owner = $customer->current_owner_sale_id ?? $customer->assigned_to ?? $customer->user_id;

        return (int) $owner === (int) $user->id || ($user->hasRole(['manager', 'manager_sale', 'sale_manager']) && $user->canViewAllMonitoringCustomers());
    }

    private function change(array $group, callable $callback): void
    {
        $key = $this->prefix().$this->identity($group);
        Cache::lock('customer-debt-policy-write-'.hash('sha256', $key), 15)->block(5, function () use ($group, $key, $callback) {
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

    public function save(array $group, array $data, int $userId, bool $isAdmin = false, ?int $expectedVersion = null, ?User $editingUser = null): void
    {
        $this->change($group, function ($key, $current, $lockedCustomer) use ($group, $data, $userId, $isAdmin, $expectedVersion, $editingUser) {
            if ($editingUser) {
                abort_unless($lockedCustomer && $this->canEditCustomer($editingUser, $lockedCustomer), 403);
            }
            if (! $isAdmin && $this->isLocked($current)) {
                throw ValidationException::withMessages(['policy' => 'Tiêu chí đã khóa. Chỉ admin được mở khóa để cho phép sửa thêm một lần.']);
            }
            if ($expectedVersion !== null && $expectedVersion !== (int) ($current['edit_version'] ?? 0)) {
                throw ValidationException::withMessages(['policy_version' => 'Cấu hình đã thay đổi. Vui lòng tải lại trang trước khi cập nhật.']);
            }
            $next = array_replace($current, [
                'allowed_orders' => isset($data['allowed_orders']) ? (int) $data['allowed_orders'] : null,
                'credit_limit' => isset($data['credit_limit']) ? (int) $data['credit_limit'] : null,
                'due_days' => isset($data['due_days']) ? (int) $data['due_days'] : null,
                'updated_by' => $userId, 'updated_at' => now()->toIso8601String(), 'staff_edit_locked' => true,
                'edit_version' => (int) ($current['edit_version'] ?? 0) + 1,
                'staff_edit_count' => (int) ($current['staff_edit_count'] ?? 0) + ($isAdmin ? 0 : 1),
            ]);
            Setting::set($key, json_encode($next, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $this->audit($this->identity($group), 'update', $userId, $current, $next);
        });
    }

    public function unlock(array $group, User $actor): void
    {
        abort_unless($actor->isAdmin(), 403, 'Chỉ admin mới được mở khóa tiêu chí công nợ.');
        $this->change($group, function ($key, $current) use ($group, $actor) {
            if (! $this->isLocked($current)) {
                return;
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

    /** Allocate receipts to the oldest sales. All lines for a customer/day form one sale. */
    public function evaluate(array $entries, array $policy, ?string $asOf = null): array
    {
        $policy = $this->applyDefaults($policy);
        $today = CarbonImmutable::parse($asOf ?? now()->toDateString())->startOfDay();
        $buckets = [];
        $credits = 0;
        $balance = 0;
        $latestDelivery = null;
        foreach ($entries as $entry) {
            if ($entry['date'] > $today->toDateString()) {
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
            $buckets[$key] ??= ['date' => $entry['date'], 'amount' => 0];
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
        $count = $balance > 0 ? count($unpaid) : 0;
        $dueDays = $policy['due_days'] ?? null;
        $dueDate = $balance > 0 && $latestDelivery !== null && $dueDays !== null ? CarbonImmutable::parse($latestDelivery)->addDays((int) $dueDays)->toDateString() : null;
        $lateDays = $dueDate !== null ? max(0, (int) CarbonImmutable::parse($dueDate)->diffInDays($today, false)) : 0;
        $orderLimit = $policy['allowed_orders'] ?? null;
        $creditLimit = $policy['credit_limit'] ?? null;
        $orderReached = $balance > 0 && $orderLimit !== null && $count >= (int) $orderLimit;
        $creditReached = $balance > 0 && $creditLimit !== null && $balance >= (int) $creditLimit;
        $dueSoon = $balance > 0 && $dueDate !== null && (int) $today->diffInDays(CarbonImmutable::parse($dueDate), false) <= 3;
        $risk = $balance <= 0 || $age < 15 ? null : ($age <= 30 ? 'medium' : ($age <= 60 ? 'high' : 'very_high'));
        $reasons = [];
        if ($orderReached) {
            $reasons[] = $count > (int) $orderLimit ? 'Vượt số đơn được phép nợ' : 'Đã chạm số đơn được phép nợ';
        }
        if ($creditReached) {
            $reasons[] = $balance > (int) $creditLimit ? 'Vượt trần công nợ' : 'Đã chạm trần công nợ';
        }
        if ($lateDays > 0) {
            $reasons[] = 'Trễ thanh toán '.$lateDays.' ngày';
        } elseif ($dueSoon) {
            $reasons[] = $dueDate === $today->toDateString() ? 'Đến hạn thanh toán hôm nay' : 'Sắp đến hạn thanh toán';
        }
        if ($risk !== null) {
            $reasons[] = 'Khoản còn nợ lâu nhất '.$age.' ngày';
        }

        return ['balance' => $balance, 'unpaid_orders' => $count, 'oldest_date' => $oldest, 'age_days' => $age, 'due_date' => $dueDate, 'latest_delivery_date' => $latestDelivery, 'overdue_days' => $lateDays, 'overdue' => $lateDays > 0, 'remind' => $orderReached || $creditReached || $dueSoon || $risk !== null, 'risk' => $risk, 'reasons' => $reasons];
    }
}
