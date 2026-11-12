<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerDebtBalanceService
{
    public const TABLE = 'customer_debt_balance_adjustments';

    public function canAdjust(User $user): bool
    {
        return $user->hasRole(['admin', 'account', 'accountant', 'accounting']);
    }

    public function identity(array $group): string
    {
        return ! empty($group['customer_id']) ? 'customer_'.$group['customer_id'] : 'source_'.$group['key'];
    }

    public function snapshotVersion(?array $snapshot): string
    {
        return hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));
    }

    public function revision(array $target, string $snapshotVersion): string
    {
        $ids = collect($target['entries'])->pluck('adjustment_id')->filter()->sort()->values()->all();
        $keys = $target['source_keys'] ?? [$target['key']];
        sort($keys);

        return hash('sha256', json_encode([$snapshotVersion, $this->identity($target), $keys, $ids, $target['balance']], JSON_THROW_ON_ERROR));
    }

    public function apply(array $groups, Collection $customers, Collection $allowedIds, bool $canViewAll): array
    {
        $customers = $customers->keyBy('id');
        $carriers = [];
        foreach ($groups as &$group) {
            $group['adjustment_total'] = 0;
            $group['sheet_balance'] = $group['balance'];
            if ($group['customer_id']) {
                $carriers[$group['customer_id']] ??= $group['key'];
            }
        }
        unset($group);
        $records = DB::table(self::TABLE)->where('spreadsheet_id', config('services.google_sheets.debt_identity_spreadsheet_id', config('services.google_sheets.debt_spreadsheet_id')))->orderBy('id')->get();
        foreach ($records as $record) {
            if (str_starts_with($record->identity, 'customer_')) {
                $customer = $customers[(int) $record->customer_id] ?? null;
                if (! $customer || (! $canViewAll && ! $allowedIds->contains($customer->id))) {
                    continue;
                }
                $key = $carriers[$customer->id] ?? hash('sha256', 'manual-customer:'.$customer->id);
                if (! isset($groups[$key])) {
                    $groups[$key] = $this->emptyGroup($key, $record, $customer);
                }
                $carriers[$customer->id] = $key;
            } else {
                $key = $record->source_key;
                if (! isset($groups[$key])) {
                    if (! $canViewAll) {
                        continue;
                    }
                    $groups[$key] = $this->emptyGroup($key, $record, null);
                }
            }
            $delta = (int) $record->delta;
            $groups[$key]['entries'][] = [
                'date' => $record->effective_date, 'type' => 'adjustment', 'amount' => $delta, 'gift' => 0,
                'row' => (int) $record->id, 'adjustment_id' => (int) $record->id,
                'note' => $record->reason, 'actor_name' => $record->actor_name, 'created_at' => $record->created_at,
                'previous_balance' => (int) $record->previous_balance, 'new_balance' => (int) $record->new_balance,
                'code' => $groups[$key]['code'], 'name' => $groups[$key]['name'],
            ];
            $groups[$key]['adjustment_total'] += $delta;
            $groups[$key]['balance'] += $delta;
        }

        return $groups;
    }

    private function emptyGroup(string $key, object $record, ?Customer $customer): array
    {
        $owner = $customer ? ($customer->currentOwner ?? $customer->assignedTo) : null;

        return [
            'key' => $key, 'name' => $customer?->name ?? $record->customer_name, 'code' => $customer?->customer_code ?? $record->customer_code ?? '',
            'customer_id' => $customer?->id, 'system_customer' => $customer,
            'sales' => 0, 'gifts' => 0, 'payments' => 0, 'balance' => 0, 'sheet_balance' => 0, 'adjustment_total' => 0,
            'entries' => [], 'last_purchase_date' => null, 'last_payment_date' => null,
            'manual_mapping' => false, 'matched_by' => 'adjustment', 'manual_only' => true, 'ambiguous' => false,
            'sale_key' => $owner ? 'user:'.$owner->id : 'unassigned',
            'sale_label' => $owner ? (trim((string) $owner->short_name) ?: $owner->name) : ($record->sale_name ?: 'Chưa xác định'),
            'sale_origin' => $owner ? 'system' : null,
        ];
    }

    /** Resolver runs under the same lock as Sheet replacement and customer mapping. */
    public function setActual(User $actor, array $data, callable $resolveTarget): object
    {
        abort_unless($this->canAdjust($actor), 403);

        return Cache::lock('customer-debt-load', 60)->block(5, function () use ($actor, $data, $resolveTarget) {
            return DB::transaction(function () use ($actor, $data, $resolveTarget) {
                $target = $resolveTarget();
                if (! empty($target['customer_id'])) {
                    Customer::query()->whereKey($target['customer_id'])->lockForUpdate()->firstOrFail();
                }
                $identity = $this->identity($target);
                $existing = DB::table(self::TABLE)->where('request_id', $data['request_id'])->first();
                if ($existing) {
                    if ($existing->identity !== $identity || $existing->spreadsheet_id !== config('services.google_sheets.debt_identity_spreadsheet_id', config('services.google_sheets.debt_spreadsheet_id')) || (int) $existing->created_by !== (int) $actor->id || (int) $existing->new_balance !== (int) $data['new_balance'] || $existing->reason !== $data['reason']) {
                        throw ValidationException::withMessages(['request_id' => 'Yêu cầu này đã được xử lý. Vui lòng tải lại trang trước khi điều chỉnh tiếp.']);
                    }

                    return $existing;
                }
                if (! hash_equals($target['balance_revision'], $data['revision'])) {
                    throw ValidationException::withMessages(['revision' => 'Công nợ hoặc dữ liệu Sheet đã thay đổi. Vui lòng tải lại trang để kiểm tra số dư mới nhất.']);
                }
                $balance = (int) $target['balance'];
                $newBalance = (int) $data['new_balance'];
                $id = DB::table(self::TABLE)->insertGetId([
                    'request_id' => $data['request_id'], 'spreadsheet_id' => config('services.google_sheets.debt_identity_spreadsheet_id', config('services.google_sheets.debt_spreadsheet_id')),
                    'identity' => $identity, 'customer_id' => $target['customer_id'] ?? null, 'source_key' => $data['source_key'],
                    'customer_name' => Str::limit($target['name'], 255, ''), 'customer_code' => $target['code'] ?? '', 'sale_name' => $target['sale_label'] ?? '',
                    'sheet_balance' => $balance - (int) ($target['adjustment_total'] ?? 0),
                    'previous_balance' => $balance, 'new_balance' => $newBalance, 'delta' => $newBalance - $balance,
                    'effective_date' => now()->toDateString(), 'reason' => $data['reason'],
                    'created_by' => $actor->id, 'actor_name' => Str::limit($actor->name ?: 'Người dùng #'.$actor->id, 255, ''),
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                return DB::table(self::TABLE)->where('id', $id)->first();
            }, 3);
        });
    }
}
