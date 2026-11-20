<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
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
