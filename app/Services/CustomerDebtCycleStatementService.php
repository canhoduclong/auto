<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerDebtCycleStatementService
{
    public function ledgerKey(array $customer): string
    {
        $identity = app(CustomerDebtPolicyService::class)->identity($customer);
        $source = config('services.google_sheets.debt_identity_spreadsheet_id', config('services.google_sheets.debt_spreadsheet_id'));

        return hash('sha256', $source.'|'.$identity);
    }

    private function prefix(array $customer): string
    {
        return 'customer_debt_cycle_'.$this->ledgerKey($customer).'_';
    }

    public function history(array $customer): array
    {
        $prefix = $this->prefix($customer);
        $statements = [];
        foreach (Setting::query()->where('key', 'like', $prefix.'%')->orderBy('key')->get(['key', 'value']) as $setting) {
            if (str_starts_with($setting->key, $prefix) && $setting->value !== '') {
                $statements[substr($setting->key, strlen($prefix))] = $this->decode($setting->key, $setting->value);
            }
        }

        return $statements;
    }

    public function statement(string $ledger, string $date): ?array
    {
        $key = 'customer_debt_cycle_'.$ledger.'_'.$date;
        $value = Setting::query()->where('key', $key)->value('value');

        return $value === null || $value === '' ? null : $this->decode($key, $value);
    }

    private function archivePrefix(string $ledger): string
    {
        return 'customer_debt_cycle_archive_'.$ledger.'_';
    }

    public function archives(string $ledger): array
    {
        $prefix = $this->archivePrefix($ledger);
        return Setting::query()->where('key', 'like', $prefix.'%')->latest('id')->get(['key', 'value'])
            ->filter(fn ($setting) => str_starts_with($setting->key, $prefix))
            ->map(fn ($setting) => $this->decode($setting->key, $setting->value))->values()->all();
    }

    public function archive(string $ledger, string $date, string $id): ?array
    {
        $key = $this->archivePrefix($ledger).$date.'_'.$id;
        $value = Setting::query()->where('key', $key)->value('value');

        return $value === null ? null : $this->decode($key, $value);
    }

    /** Caller holds the Sheet refresh and customer policy locks. */
    public function reopen(array $customer, string $date, string $number, string $reason, int $actor): void
    {
        $history = $this->history($customer);
        $statement = $history[$date] ?? null;
        if (! $statement || array_key_last($history) !== $date || $statement['number'] !== $number) {
            throw ValidationException::withMessages(['cycle_date' => 'Chỉ có thể gỡ chốt kỳ gần nhất. Vui lòng tải lại danh sách kỳ.']);
        }
        if ($this->draft($customer)) {
            throw ValidationException::withMessages(['cycle_date' => 'Khách đang có kỳ nháp. Vui lòng xử lý hoặc bỏ kỳ nháp trước khi gỡ chốt.']);
        }
        DB::transaction(function () use ($customer, $statement, $date, $reason, $actor) {
            $archive = $statement + [
                'archive_id' => (string) Str::uuid(), 'voided_at' => now()->toIso8601String(),
                'voided_by' => $actor, 'void_reason' => $reason,
            ];
            $this->store($this->archivePrefix($archive['ledger']).$date.'_'.$archive['archive_id'], $archive);
            $this->store($this->prefix($customer).$date, null);
            $this->store($this->draftKey($customer), [
                'name' => ($statement['name'] ?? null) ?: 'Kỳ công nợ '.$date,
                'from' => $statement['from'], 'to' => $statement['to'],
                'bill_keys' => $statement['bill_keys'] ?? [], 'automatic' => ! isset($statement['bill_keys']),
                'manual_adjustment' => $statement['manual_adjustment'] ?? 0,
                'adjustment_reason' => $statement['adjustment_reason'] ?? null,
                'version' => (string) Str::uuid(),
            ]);
        });
    }

    public function printable(string $ledger, string $date, ?string $id = null, ?string $archiveId = null): ?array
    {
        if ($archiveId !== null) {
            return $this->archive($ledger, $date, $archiveId);
        }
        $statement = $this->statement($ledger, $date);
        if ($id === null && $statement && empty($statement['statement_id'])) {
            return $statement;
        }
        if ($id !== null && ($statement['statement_id'] ?? null) === $id) {
            return $statement;
        }

        foreach (array_reverse($this->archives($ledger)) as $archived) {
            if ($archived['to'] === $date && ($archived['statement_id'] ?? null) === $id) {
                return $archived;
            }
        }

        return $id === null ? $statement : null;
    }

    private function draftKey(array $customer): string
    {
        return 'customer_debt_cycle_draft_'.$this->ledgerKey($customer);
    }

    public function draft(array $customer): ?array
    {
        $key = $this->draftKey($customer);
        $value = Setting::query()->where('key', $key)->value('value');

        return $value === null || $value === '' ? null : $this->decode($key, $value);
    }

    public function autoDraft(array $customer): void
    {
        $preview = $this->preview($customer, $this->history($customer));
        if (! $preview) {
            throw ValidationException::withMessages(['cycle_date' => 'Cần thiết lập thanh toán định kỳ trước khi tạo kỳ tự động.']);
        }
        $this->store($this->draftKey($customer), [
            'name' => 'Kỳ công nợ '.CarbonImmutable::parse($preview['from'])->format('d/m/Y').' – '.CarbonImmutable::parse($preview['to'])->format('d/m/Y'),
            'from' => $preview['from'], 'to' => $preview['to'],
            'bill_keys' => [], 'automatic' => true,
            'version' => (string) Str::uuid(),
        ]);
    }

    public function draftBills(array $draft, array $preview): array
    {
        if (! empty($draft['automatic'])) {
            return array_keys(array_filter($preview['bills'], fn ($bill) => ! $bill['carried']));
        }

        return array_values(array_intersect($draft['bill_keys'], array_keys($preview['bills'])));
    }

    /** Caller holds the Sheet refresh and customer policy locks. */
    public function saveDraft(array $customer, array $data, ?array $selectedBills, ?array $draft): void
    {
        $history = $this->history($customer);
        $preview = $this->preview($customer, $history, $data['cycle_date'], $data['cycle_from'] ?? null, $selectedBills);
        if (! $preview || ! hash_equals($preview['revision'], $data['revision'])) {
            throw ValidationException::withMessages(['cycle_date' => 'Dữ liệu đã thay đổi. Vui lòng xem lại các bill trước khi lưu kỳ.']);
        }
        $this->store($this->draftKey($customer), [
            'name' => $data['cycle_name'],
            'from' => $preview['from'], 'to' => $preview['to'],
            'bill_keys' => $draft === null ? [] : $preview['bill_keys'],
            'manual_adjustment' => $data['cycle_adjustment'] ?? 0,
            'adjustment_reason' => $data['cycle_adjustment_reason'] ?? null,
            'version' => (string) Str::uuid(),
        ]);
    }

    public function clearDraft(array $customer): void
    {
        $this->store($this->draftKey($customer), null);
    }

    private function decode(string $key, string $value): array
    {
        if ($value === 'chunked') {
            $prefix = 'cycle_statement_chunk_'.hash('sha256', $key).'_';
            $value = Setting::query()->where('key', 'like', $prefix.'%')->orderBy('key')->pluck('value')->implode('');
        }
        if (str_starts_with($value, 'gz:')) {
            $value = gzdecode(base64_decode(substr($value, 3), true));
        }

        return json_decode($value, true, flags: JSON_THROW_ON_ERROR);
    }

    private function billKey(array $entry): string
    {
        $code = trim($entry['order_code'] ?? '');
        $reference = $code !== '' ? 'bill:'.mb_strtoupper($code) : app(CustomerDebtPaymentAllocation::class)->key($entry);

        return hash('sha256', $entry['date'].'|'.$reference);
    }

    public function preview(array $customer, array $history, ?string $requestedDate = null, ?string $requestedFrom = null, ?array $selectedBills = null, int $manualAdjustment = 0, ?string $adjustmentReason = null): ?array
    {
        $policy = $customer['policy'];
        $today = CarbonImmutable::today(config('app.timezone'));
        $latest = end($history) ?: null;
        $rules = app(CustomerDebtPolicyService::class);
        $nextStart = $latest ? CarbonImmutable::parse($latest['to'])->addDay() : null;
        $anchorDate = $requestedFrom ?? $requestedDate ?? max($today->toDateString(), $nextStart?->toDateString() ?? '');
        $period = collect(CustomerDebtPolicyService::settlementPeriods($policy))->first(
            fn ($period) => ($period['from'] === null || $period['from'] <= $anchorDate)
                && ($period['to'] === null || $period['to'] >= $anchorDate)
        );
        if (($period['method'] ?? null) !== 'cycle') {
            if ($requestedDate !== null || $requestedFrom !== null) {
                throw ValidationException::withMessages(['cycle_date' => 'Khoảng đã chọn phải thuộc giai đoạn thanh toán theo chu kỳ.']);
            }

            return null;
        }

        $periodStart = $period['from'] ? CarbonImmutable::parse($period['from']) : null;
        $periodEnd = $period['to'] ? CarbonImmutable::parse($period['to']) : null;
        if ($nextStart) {
            $from = $periodStart && $periodStart->gt($nextStart) ? $periodStart : $nextStart;
            if ($requestedFrom !== null && $requestedFrom !== $from->toDateString()) {
                throw ValidationException::withMessages(['cycle_date' => 'Kỳ mới phải bắt đầu ngay sau kỳ trước hoặc từ ngày bắt đầu giai đoạn theo chu kỳ mới.']);
            }
            [$close] = $rules->cycleDates($policy, $from);
        } else {
            [$close] = $rules->cycleDates($policy, $today);
            if ($close->gt($today)) {
                $anchor = $policy['cycle_type'] === 'monthly' ? $close->startOfMonth()->subMonth() : $close->subWeek();
                [$close] = $rules->cycleDates($policy, $anchor);
            }
            $close = $requestedDate ? CarbonImmutable::parse($requestedDate) : $close;
            if ($periodStart && $close->lt($periodStart)) {
                [$close] = $rules->cycleDates($policy, $periodStart);
            }
            $priorAnchor = $policy['cycle_type'] === 'monthly' ? $close->startOfMonth()->subMonth() : $close->subWeek();
            [$previousClose] = $rules->cycleDates($policy, $priorAnchor);
            $from = $requestedFrom ? CarbonImmutable::parse($requestedFrom) : $previousClose->addDay();
            if (! $requestedFrom && $from->gt($close)) {
                $from = $close;
            }
            if (! $requestedFrom && $periodStart && $from->lt($periodStart)) {
                $from = $periodStart;
            }
        }
        $close = $requestedDate ? CarbonImmutable::parse($requestedDate) : $close;
        if (! $requestedDate && $periodEnd && $close->gt($periodEnd)) {
            $close = $periodEnd;
        }
        if ($close->lt($from) || ($periodStart && $from->lt($periodStart)) || ($periodEnd && $close->gt($periodEnd))) {
            throw ValidationException::withMessages(['cycle_date' => 'Từ ngày và đến ngày phải hợp lệ và thuộc cùng một giai đoạn thanh toán theo chu kỳ.']);
        }
        $due = $rules->cycleDueDate($policy, $close);

        $start = $from->toDateString();
        $end = $close->toDateString();
        $sheets = app(CustomerDebtSheetService::class);
        $emptyAmounts = ['sales' => 0, 'gifts' => 0, 'payments' => 0, 'adjustments' => 0];
        $ledgerOpening = 0;
        $entries = $daily = $bills = $catalog = $included = [];
        $asOf = min($end, $today->toDateString());
        $firstStart = $history ? reset($history)['from'] : $start;
        $assigned = $legacyRanges = [];
        foreach ($history as $statement) {
            if (isset($statement['bill_keys'])) {
                foreach ($statement['bill_keys'] as $key) {
                    $assigned[$key] = true;
                }
            } else {
                $legacyRanges[] = [$statement['from'], $statement['to']];
            }
        }
        $legacyRanges = collect($legacyRanges);
        $wanted = $selectedBills === null ? null : array_fill_keys($selectedBills, true);
        foreach ($customer['entries'] as $entry) {
            $date = $entry['date'];
            $isBill = $entry['type'] === 'sale';
            if ($isBill) {
                $key = $this->billKey($entry);
                $alreadyAssigned = isset($assigned[$key]) || $legacyRanges->contains(fn ($range) => $date >= $range[0] && $date <= $range[1]);
                $outsideCycleBeforeStart = $date < $start && CustomerDebtPolicyService::currentSettlementMethod($policy, $date) !== 'cycle';
                $reason = match (true) {
                    $alreadyAssigned => 'Đã gán vào kỳ đã chốt',
                    $date < $firstStart || $outsideCycleBeforeStart => 'Đã tính vào dư đầu kỳ',
                    $date > $end => 'Sau ngày kết thúc kỳ',
                    default => null,
                };
                $catalog[$key] ??= [
                    'date' => $date,
                    'label' => trim($entry['order_code'] ?? '') ?: app(CustomerDebtPaymentAllocation::class)->key($entry),
                    'sales' => 0, 'gifts' => 0, 'carried' => $date < $start, 'unavailable' => $reason,
                ];
                $catalog[$key]['sales'] += $entry['amount'];
                $catalog[$key]['gifts'] += $entry['gift'] ?? 0;
                if ($reason === null) {
                    $bills[$key] = $catalog[$key];
                    $entries[] = $entry;
                    if ($wanted !== null && ! isset($wanted[$key])) {
                        continue;
                    }
                    $included[$key] = true;
                    $daily[$date] ??= $emptyAmounts;
                    $daily[$date]['sales'] += $entry['amount'];
                    $daily[$date]['gifts'] += $entry['gift'] ?? 0;
                    continue;
                }
            }
            if ($date > $asOf) {
                continue;
            }
            if ($date < $start) {
                $ledgerOpening += $sheets->movement($entry);
            } elseif (! $isBill) {
                $entries[] = $entry;
                $daily[$date] ??= $emptyAmounts;
                if ($entry['type'] === 'adjustment') {
                    $daily[$date]['adjustments'] += $entry['amount'];
                } elseif ($entry['type'] === 'payment') {
                    $daily[$date]['payments'] += $entry['amount'];
                }
            }
        }
        if ($wanted !== null && array_diff_key($wanted, $bills)) {
            throw ValidationException::withMessages(['cycle_date' => 'Có bill đã được gán hoặc thay đổi. Vui lòng tải lại và chọn bill.']);
        }
        uasort($catalog, fn ($a, $b) => [$b['date'], $a['label']] <=> [$a['date'], $b['label']]);
        uasort($bills, fn ($a, $b) => [$a['date'], $a['label']] <=> [$b['date'], $b['label']]);
        ksort($daily);
        $totals = [];
        foreach (array_keys($emptyAmounts) as $field) {
            $totals[$field] = array_sum(array_column($daily, $field));
        }
        $opening = $latest['closing'] ?? $ledgerOpening;
        $priorCorrection = $ledgerOpening + array_sum(array_column($history, 'manual_adjustment')) - $opening;
        $netNew = $totals['sales'] - $totals['gifts'] + $totals['adjustments'] + $manualAdjustment;
        $paidAgainstOpening = min(max(0, $opening + $priorCorrection), max(0, $totals['payments']));
        $preview = $totals + [
            'from' => $start, 'to' => $end, 'due' => $due->toDateString(), 'cycle_type' => $policy['cycle_type'],
            'manual_adjustment' => $manualAdjustment, 'adjustment_reason' => $adjustmentReason,
            'opening' => $opening, 'prior_correction' => $priorCorrection, 'opening_paid' => $paidAgainstOpening,
            'opening_remaining' => $opening + $priorCorrection - $paidAgainstOpening,
            'net_new' => $netNew, 'daily' => $daily, 'closing' => $opening + $priorCorrection + $netNew - $totals['payments'],
            'customer_name' => $customer['name'], 'customer_code' => $customer['code'] ?? '',
            'company' => Setting::get('company_legal_name', 'CÔNG TY CỔ PHẦN THỰC PHẨM HOÀNG LONG TNT'),
            'can_close' => $close->lte($today),
        ];
        $preview['revision'] = hash('sha256', json_encode([
            $this->prefix($customer), $start, $end, $preview['due'], $opening, $priorCorrection, $bills, $entries, $policy['edit_version'],
        ], JSON_THROW_ON_ERROR));
        $preview['bills'] = $bills;
        $preview['bill_catalog'] = $catalog;
        $preview['bill_keys'] = array_keys($included);

        return $preview;
    }

    /** Caller holds the Sheet refresh lock and the customer policy lock. */
    public function close(array $customer, string $date, string $revision, int $actor, ?string $from = null, ?array $selectedBills = null, ?string $name = null, int $manualAdjustment = 0, ?string $adjustmentReason = null): array
    {
        $history = $this->history($customer);
        if (isset($history[$date]) && hash_equals($history[$date]['revision'], $revision)
            && ($history[$date]['manual_adjustment'] ?? 0) === $manualAdjustment
            && ($history[$date]['adjustment_reason'] ?? null) === $adjustmentReason
            && ($name === null || ($history[$date]['name'] ?? null) === $name)
            && ($selectedBills === null || ! array_diff($selectedBills, $history[$date]['bill_keys'] ?? []) && ! array_diff($history[$date]['bill_keys'] ?? [], $selectedBills))) {
            return $history[$date];
        }
        $preview = $this->preview($customer, $history, $date, $from, $selectedBills, $manualAdjustment, $adjustmentReason);
        if (! $preview || ! $preview['can_close'] || ! hash_equals($preview['revision'], $revision)) {
            throw ValidationException::withMessages(['cycle_date' => 'Kỳ chưa đến ngày chốt hoặc dữ liệu đã đổi. Vui lòng xem trước lại.']);
        }
        $preview['name'] = $name;
        $preview['ledger'] = $this->ledgerKey($customer);
        $preview['number'] = 'CN-'.str_replace('-', '', $date).'-'.strtoupper(substr(hash('sha256', $this->prefix($customer)), 0, 12));
        $preview['statement_id'] = (string) Str::uuid();
        $preview['number'] .= '-'.strtoupper(substr($preview['statement_id'], 0, 8));
        $preview['closed_at'] = now()->toIso8601String();
        $preview['closed_by'] = $actor;
        $preview['bills'] = array_intersect_key($preview['bills'], array_fill_keys($preview['bill_keys'], true));
        unset($preview['bill_catalog']);
        $this->store($this->prefix($customer).$date, $preview);

        return $preview;
    }

    private function store(string $key, ?array $record): void
    {
        $value = $record === null ? '' : 'gz:'.base64_encode(gzencode(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)));
        $prefix = 'cycle_statement_chunk_'.hash('sha256', $key).'_';
        DB::transaction(function () use ($key, $value, $prefix) {
            Setting::query()->where('key', 'like', $prefix.'%')->delete();
            if (strlen($value) <= 60000) {
                Setting::set($key, $value);

                return;
            }
            foreach (str_split($value, 60000) as $index => $part) {
                Setting::set($prefix.str_pad((string) $index, 8, '0', STR_PAD_LEFT), $part);
            }
            Setting::set($key, 'chunked');
        });
    }
}
