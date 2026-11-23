<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerDebtManualAllocationService
{
    private function key(array $customer): string
    {
        $source = config('services.google_sheets.debt_identity_spreadsheet_id', config('services.google_sheets.debt_spreadsheet_id'));
        $identity = app(CustomerDebtPolicyService::class)->identity($customer);

        return 'customer_debt_manual_allocation_'.hash('sha256', $source.'|'.$identity);
    }

    public function records(array $customer): array
    {
        $value = Setting::query()->where('key', $this->key($customer))->value('value');

        return json_decode($value ?? '[]', true, flags: JSON_THROW_ON_ERROR);
    }

    public function revision(array $customer, array $records): string
    {
        return hash('sha256', json_encode([
            $this->key($customer), $customer['balance_revision'], $customer['policy']['edit_version'], $records,
        ], JSON_THROW_ON_ERROR));
    }

    public function billGroups(array $records): Collection
    {
        $groups = collect($records)->filter(fn ($record) => isset($record['confirmed_group']))
            ->groupBy('confirmed_group', true);
        $next = (int) $groups->max(fn ($members) => $members->first()['batch']['number'] ?? 0);

        return $groups->map(function ($members) use (&$next) {
            return [
                'members' => $members,
                'batch' => $members->first()['batch'] ?? ['number' => ++$next],
            ];
        });
    }

    /** The caller holds the Sheet refresh lock and a database transaction. */
    public function confirmBills(array $customer, array $data, User $actor, CustomerDebtBillReconciliation $bills, CustomerDebtPaymentAllocation $allocation): ?array
    {
        abort_unless($actor->canManageCustomerDebt(), 403);
        $before = $records = $this->records($customer);
        if (! hash_equals($this->revision($customer, $records), $data['revision'])) {
            throw ValidationException::withMessages(['allocation' => 'Dữ liệu đã thay đổi. Vui lòng tải lại tab Thanh toán theo Bill.']);
        }
        $history = $this->billGroups($records);
        $sequenceKey = $this->key($customer).'_bill_sequence';
        $number = max((int) Setting::query()->where('key', $sequenceKey)->value('value'), (int) $history->max('batch.number'));
        foreach ($history as $entry) {
            $records[$entry['members']->keys()->first()]['batch'] = $entry['batch'];
        }

        $group = $data['cancel_group'] ?? (string) Str::uuid();
        $reopen = null;
        if (isset($data['cancel_group'])) {
            $entry = $history->get($group);
            abort_unless($entry, 404);
            $reopen = $entry['batch'];
            $reopen['payment_keys'] ??= $entry['members']->map(fn ($record, $key) => $record['payment_key'] ?? $key)->values()->all();
            $reopen['line_keys'] = $entry['members']->pluck('items')->collapse()->pluck('target')->unique()->values()->all();
            $records = array_filter($records, fn ($record) => ($record['confirmed_group'] ?? null) !== $group);
        } else {
            $today = today()->toDateString();
            $entries = array_values(array_filter($customer['entries'], fn ($entry) => $entry['date'] <= $today));
            $policy = $customer['policy'];
            $changes = array_filter($policy['settlement_changes'], fn ($date) => $date <= $today, ARRAY_FILTER_USE_KEY);
            $reconcile = fn ($saved) => $allocation->reconcile($entries, $policy['settlement_method'], $customer['customer_id'], $changes, $saved);
            $baseline = $reconcile($records);
            $period = $bills->periods($entries, $policy, $baseline, $records)[$data['period_from']] ?? null;
            abort_unless($period, 422, 'Giai đoạn Bill đã thay đổi.');
            $exact = (bool) ($data['match_exact'] ?? false);
            $pairs = $exact ? $period['exact_pairs'] : [];
            $billKeys = $exact ? array_values($pairs) : $data['bill_keys'];
            $paymentKeys = $exact ? array_keys($pairs) : $data['payment_keys'];
            if ($billKeys === []) {
                throw ValidationException::withMessages(['allocation' => 'Không còn cặp Bill và khoản thu khớp số tiền để xác nhận.']);
            }
            $selected = $period['bills']->only($billKeys)->where('remaining', '>', 0);
            $payments = $period['payments']->only($paymentKeys)->where('can_allocate', true);
            if ($selected->count() !== count($billKeys) || $payments->count() !== count($paymentKeys)) {
                throw ValidationException::withMessages(['allocation' => 'Có Bill hoặc khoản thu đã khớp hoặc đã thay đổi. Vui lòng tải lại trang.']);
            }
            $targets = [];
            foreach ($selected as $bill) {
                $targets += $bill['lines']->all();
            }
            $remaining = array_map(fn ($line) => $line['remaining'], $targets);
            $date = max($selected->max('date'), $payments->max('date'));
            $appliedByPayment = $appliedByTarget = [];
            $newRecords = [];
            $sourcePayments = collect($entries)->where('type', 'payment')->keyBy(fn ($entry) => $allocation->key($entry));
            foreach ($payments as $key => $payment) {
                $available = $payment['pending'];
                $items = [];
                $eligible = $exact ? $selected[$pairs[$key]]['lines']->keys()->all() : array_keys($remaining);
                $receiptRemaining = array_intersect_key($remaining, array_flip($eligible));
                while ($available > 0 && $receiptRemaining !== []) {
                    $target = array_key_first($receiptRemaining);
                    $amount = min($available, $remaining[$target]);
                    $items[] = ['target' => $target, 'amount' => $amount, 'signature' => $targets[$target]['signature']];
                    $available -= $amount;
                    $appliedByTarget[$target] = ($appliedByTarget[$target] ?? 0) + $amount;
                    $remaining[$target] -= $amount;
                    $receiptRemaining[$target] -= $amount;
                    if ($remaining[$target] === 0) {
                        unset($remaining[$target], $receiptRemaining[$target]);
                    }
                }
                if ($items === []) {
                    continue;
                }
                $appliedByPayment[$key] = $payment['pending'] - $available;
                $recordKey = isset($records[$key]) ? $key.'@'.$group : $key;
                $newRecords[$recordKey] = [
                    'payment_key' => $key,
                    'confirmed_group' => $group,
                    'date' => $date,
                    'signature' => $allocation->signature($sourcePayments->get($key)), 'items' => $items,
                    'updated_by' => $actor->id, 'updated_at' => now()->toIso8601String(),
                ];
            }

            $newRecords[array_key_first($newRecords)]['batch'] = [
                'number' => ++$number,
                'period_from' => $period['from'],
                'bill_keys' => $selected->keys()->all(),
                'bill_labels' => $selected->map(fn ($bill) => $bill['label'].' ('.$bill['date'].')')->values()->all(),
                'payment_keys' => $payments->keys()->all(),
                'shortfall' => $selected->sum('remaining') - array_sum($appliedByTarget),
                'surplus' => $payments->sum('pending') - array_sum($appliedByPayment),
            ];
            $records += $newRecords;
            $result = $reconcile($records);
            $unchanged = array_diff_key($baseline['lines'], $targets) === array_diff_key($result['lines'], $targets)
                && array_diff_key($baseline['unmatched'], $payments->all()) === array_diff_key($result['unmatched'], $payments->all())
                && array_diff_key($newRecords, $result['confirmedApplied']) === [];
            foreach ($baseline['manualApplied'] as $key => $items) {
                $unchanged = $unchanged && array_slice($result['manualApplied'][$key] ?? [], 0, count($items)) === $items;
            }
            foreach ($targets as $key => $line) {
                $unchanged = $unchanged && $result['lines'][$key]['remaining'] === $line['remaining'] - ($appliedByTarget[$key] ?? 0);
            }
            foreach ($payments as $key => $entry) {
                $unchanged = $unchanged && ($result['unmatched'][$key]['amount'] ?? 0) === $entry['pending'] - ($appliedByPayment[$key] ?? 0);
            }
            if (! $unchanged) {
                throw ValidationException::withMessages(['allocation' => 'Nhóm đã chọn ảnh hưởng đến khoản ghép khác. Vui lòng kiểm tra lại.']);
            }
        }

        Setting::set($sequenceKey, (string) $number);
        Setting::set($this->key($customer), json_encode($records, JSON_THROW_ON_ERROR));
        Setting::set('customer_debt_manual_allocation_audit_'.Str::uuid(), json_encode([
            'identity' => $this->key($customer), 'confirmed_group' => $group, 'actor_id' => $actor->id,
            'at' => now()->toIso8601String(), 'before' => array_diff_key($before, $records), 'after' => array_diff_key($records, $before),
        ], JSON_THROW_ON_ERROR));

        return $reopen;
    }

    /** The caller holds the Sheet refresh lock and a database transaction. */
    public function save(array $customer, array $data, User $actor, CustomerDebtPaymentAllocation $allocation): void
    {
        abort_unless($actor->canManageCustomerDebt(), 403);
        $records = $this->records($customer);
        if (! hash_equals($this->revision($customer, $records), $data['revision'])) {
            throw ValidationException::withMessages(['allocation' => 'Dữ liệu đã thay đổi. Vui lòng mở lại form ghép khoản thu.']);
        }
        $key = $data['payment_key'];
        $before = $records[$key] ?? null;
        if (collect($records)->contains(fn ($record, $recordKey) =>
            isset($record['confirmed_group']) && ($record['payment_key'] ?? $recordKey) === $key)) {
            throw ValidationException::withMessages(['allocation' => 'Mở tab Thanh toán theo Bill để hủy xác nhận cả nhóm.']);
        }
        unset($records[$key]);
        $after = null;
        if (! ($data['cancel'] ?? false)) {
            $entries = array_values(array_filter($customer['entries'], fn ($entry) => $entry['date'] <= today()->toDateString()));
            $receipt = collect($entries)->first(fn ($entry) => $entry['type'] === 'payment' && $allocation->key($entry) === $key);
            $policy = $customer['policy'];
            $changes = array_filter($policy['settlement_changes'], fn ($date) => $date <= today()->toDateString(), ARRAY_FILTER_USE_KEY);
            $baseline = $allocation->reconcile($entries, $policy['settlement_method'], $customer['customer_id'], $changes, $records);
            if (! $receipt || ($baseline['unmatched'][$key]['amount'] ?? 0) <= 0) {
                throw ValidationException::withMessages(['allocation' => 'Khoản thu này hiện không có tiền chờ ghép.']);
            }
            $items = [];
            foreach ($data['items'] as $item) {
                $target = $item['target'];
                $signature = $baseline['lines'][$target]['signature'] ?? null;
                $beforeDate = str_starts_with($target, 'legacy:') ? substr($target, strlen('legacy:')) : null;
                if ($signature === null && ! isset($changes[$beforeDate])) {
                    throw ValidationException::withMessages(['allocation' => 'Mục được chọn không còn trong công nợ của khách.']);
                }
                $items[] = ['target' => $target, 'amount' => (int) $item['amount'], 'signature' => $signature, 'before_date' => $beforeDate];
            }
            $after = ['signature' => $allocation->signature($receipt), 'date' => $receipt['date'], 'items' => $items, 'updated_by' => $actor->id, 'updated_at' => now()->toIso8601String()];
            $records[$key] = $after;
            $result = $allocation->reconcile($entries, $policy['settlement_method'], $customer['customer_id'], $changes, $records);
            if (isset($result['invalidManual'][$key])) {
                throw ValidationException::withMessages(['allocation' => 'Số tiền ghép vượt khoản thu chờ ghép hoặc số còn phải thu của mục đã chọn.']);
            }
        }
        if ($before === null && $after === null) {
            return;
        }
        Setting::set($this->key($customer), json_encode($records, JSON_THROW_ON_ERROR));
        Setting::set('customer_debt_manual_allocation_audit_'.Str::uuid(), json_encode([
            'identity' => $this->key($customer), 'payment_key' => $key, 'actor_id' => $actor->id,
            'at' => now()->toIso8601String(), 'before' => $before, 'after' => $after,
        ], JSON_THROW_ON_ERROR));
    }
}
