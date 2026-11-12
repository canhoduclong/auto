<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\CarbonImmutable;

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

    private function applyDefaults(array $policy): array
    {
        return array_replace($this->defaults(), array_filter($policy, fn ($value) => $value !== null));
    }

    public function effective(array $group, array $policies): array
    {
        return $this->applyDefaults($policies[$this->identity($group)] ?? $policies['source_'.($group['key'] ?? '')] ?? []);
    }

    public function save(array $group, array $data, int $userId): void
    {
        Setting::set($this->prefix().$this->identity($group), json_encode([
            'allowed_orders' => isset($data['allowed_orders']) ? (int) $data['allowed_orders'] : null,
            'credit_limit' => isset($data['credit_limit']) ? (int) $data['credit_limit'] : null,
            'due_days' => isset($data['due_days']) ? (int) $data['due_days'] : null,
            'updated_by' => $userId, 'updated_at' => now()->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
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
        foreach ($entries as $entry) {
            if ($entry['date'] > $today->toDateString()) {
                continue;
            }
            if ($entry['type'] === 'payment') {
                $credits += $entry['amount'];
                $balance -= $entry['amount'];

                continue;
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
        $dueDate = $oldest !== null && $dueDays !== null ? CarbonImmutable::parse($oldest)->addDays((int) $dueDays)->toDateString() : null;
        $lateDays = $dueDays !== null && $balance > 0 ? max(0, $age - (int) $dueDays) : 0;
        $orderLimit = $policy['allowed_orders'] ?? null;
        $creditLimit = $policy['credit_limit'] ?? null;
        $orderReached = $balance > 0 && $orderLimit !== null && $count >= (int) $orderLimit;
        $creditReached = $balance > 0 && $creditLimit !== null && $balance >= (int) $creditLimit;
        $dueSoon = $balance > 0 && $dueDays !== null && $age >= max(0, (int) $dueDays - 3);
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

        return ['balance' => $balance, 'unpaid_orders' => $count, 'oldest_date' => $oldest, 'age_days' => $age, 'due_date' => $dueDate, 'overdue_days' => $lateDays, 'overdue' => $lateDays > 0, 'remind' => $orderReached || $creditReached || $dueSoon || $risk !== null, 'risk' => $risk, 'reasons' => $reasons];
    }
}
