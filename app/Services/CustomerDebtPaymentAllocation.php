<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CustomerDebtPaymentAllocation
{
    public function reconcile(array $entries, string $method, ?int $customerId = null, array $changes = [], array $manual = [], ?string $firstChangeDate = null): array
    {
        $sales = collect($entries)->where('type', 'sale')->sortBy([['date', 'asc'], ['row', 'asc']]);
        $payments = collect($entries)->where('type', 'payment')->sortBy([['date', 'asc'], ['row', 'asc']]);
        $firstChangeDate ??= array_key_first($changes);
        $changeDate = array_key_last($changes);
        $currentMethod = $changes[$changeDate] ?? $method;
        $previous = null;
        if ($changeDate) {
            unset($changes[$changeDate]);
            $earlierEntries = array_values(array_filter($entries, fn ($entry) => $entry['date'] < $changeDate));
            $earlierManual = array_filter($manual, fn ($record) => ($record['date'] ?? $changeDate) < $changeDate);
            $previous = $this->reconcile($earlierEntries, $method, $customerId, $changes, $earlierManual, $firstChangeDate);
            $manual = array_diff_key($manual, $previous['manualApplied']);
            $payments = $payments->where('date', '>=', $changeDate);
        }
        $systemCodes = ($changeDate || $method !== 'batch') && $customerId ? $this->knownOrderCodes($sales, $customerId) : [];
        $lines = $bills = [];
        foreach ($sales as $sale) {
            $key = $this->key($sale);
            $code = trim($sale['order_code'] ?? '') ?: ($systemCodes[$sale['date']] ?? '');
            $amount = max(0, $sale['amount'] - ($sale['gift'] ?? 0));
            $lines[$key] = $previous['lines'][$key] ?? [
                'date' => $sale['date'], 'row' => $sale['row'], 'code' => $code,
                'amount' => $amount, 'paid' => 0, 'remaining' => $amount,
                'signature' => $this->signature($sale),
            ];
            $lines[$key]['legacy'] = $changeDate && $sale['date'] < $changeDate;
            $code = $lines[$key]['code'] = $lines[$key]['code'] ?: $code;
            if ($code !== '') {
                $bills[$sale['date'].'|'.mb_strtoupper($code)][] = $key;
            }
        }

        $unmatched = $previous['unmatched'] ?? [];
        $automatic = $previous['automatic'] ?? [];
        $batchReceipts = $oldDebtReceipts = [];
        $batch = 0;
        foreach ($payments as $payment) {
            $key = $this->key($payment);
            $amount = $payment['amount'];
            if ($amount === 0) {
                continue;
            }
            if (! $changeDate && $method === 'batch') {
                $batch += $amount;
                if ($amount > 0) {
                    $batchReceipts[$key] = $amount;
                }
                continue;
            }
            $note = $payment['note'] ?? '';
            $references = $this->references($note);
            $isLinkedReceipt = ($payment['source_sheet'] ?? '') === 'nhatkybanhang' && isset($lines[$key]);
            $normalizedNote = Str::ascii($note);
            if ($changeDate && $amount > 0 && $references === [] && ! $isLinkedReceipt && ! preg_match('/\bbill\b/i', $normalizedNote)) {
                $paysOldDebt = preg_match('/\bno\s+cu\b/i', $normalizedNote) === 1;
                $paysByBatch = $this->hasBatchReference($note);
                if ($paysOldDebt || ($currentMethod === 'bill' && $paysByBatch)) {
                    $oldDebtReceipts[$key] = $amount;
                    continue;
                }
                if ($currentMethod === 'batch' || ($currentMethod === 'mixed' && $paysByBatch)) {
                    $batchReceipts[$key] = $amount;
                    continue;
                }
            }
            if (! $changeDate && $method === 'mixed' && $references === [] && $this->hasBatchReference($note)) {
                $batch += $amount;
                if ($amount > 0) {
                    $batchReceipts[$key] = $amount;
                }
                continue;
            }
            $targets = $isLinkedReceipt
                ? [$key]
                : $this->billTargets($references, $bills);
            if ($amount < 0 || $targets === []) {
                $unmatched[$key] = ['amount' => $amount, 'reason' => $amount < 0 ? 'Khoản thu âm cần đối chiếu' : 'Thiếu hoặc không khớp ngày giao và mã Bill'];
                continue;
            }

            $applied = [];
            $left = $this->apply($amount, $targets, $lines, $applied);
            $automatic[$key] = $applied;
            if ($left > 0) {
                $unmatched[$key] = ['amount' => $left, 'reason' => 'Số thu vượt phần còn phải thu của Bill đã ghi'];
            }
        }

        $legacyTargets = array_keys(array_filter($lines, fn ($line) => $line['legacy']));
        $currentTargets = array_keys(array_filter($lines, fn ($line) => ! $line['legacy']));
        $credit = $previous['credit'] ?? 0;
        $batchSurplus = [];
        if ($changeDate) {
            foreach ($previous['batchSurplus'] as $key => $amount) {
                $unmatched[$key] = ['amount' => $amount, 'reason' => 'Tiền theo đợt còn dư trước ngày chuyển'];
            }
            $credit = min(0, $credit);
            foreach ($oldDebtReceipts + $batchReceipts as $key => $amount) {
                $paysOldDebt = isset($oldDebtReceipts[$key]);
                $applied = [];
                $left = $this->apply($amount, $paysOldDebt ? $legacyTargets : $currentTargets, $lines, $applied);
                $automatic[$key] = $applied;
                if ($left > 0) {
                    $unmatched[$key] = ['amount' => $left, 'reason' => $paysOldDebt ? 'Tiền thu còn dư sau khi cấn nợ cũ' : 'Tiền theo đợt còn dư trong kỳ áp dụng'];
                }
            }
        } else {
            $credit = $batch > 0 ? $this->apply($batch, array_keys($lines), $lines) : $batch;
            // Aggregate batch allocation consumes the oldest receipts first.
            $surplus = $credit;
            foreach (array_reverse($batchReceipts, true) as $key => $amount) {
                if ($surplus <= 0) {
                    break;
                }
                $batchSurplus[$key] = min($surplus, $amount);
                $surplus -= $batchSurplus[$key];
            }
        }

        $receipts = collect($entries)->where('type', 'payment')->keyBy(fn ($entry) => $this->key($entry));
        $manualApplied = $previous['manualApplied'] ?? [];
        $invalidManual = [];
        foreach ($manual as $key => $record) {
            $receipt = $receipts->get($key);
            if (! $receipt) {
                continue;
            }
            if ($record['signature'] !== $this->signature($receipt)) {
                $invalidManual[$key] = 'Dòng thu đã thay đổi trong nguồn. Cần kiểm tra lại lần ghép.';
                continue;
            }
            $available = max(0, $unmatched[$key]['amount'] ?? 0);
            $trial = $lines;
            foreach ($record['items'] as $item) {
                $target = $item['target'];
                $beforeDate = $target === 'legacy' ? $firstChangeDate : ($item['before_date'] ?? null);
                $targets = $beforeDate && $changeDate && $beforeDate <= $changeDate
                    ? array_keys(array_filter($trial, fn ($line) => $line['date'] < $beforeDate))
                    : [$target];
                if ($available < $item['amount'] || (isset($trial[$target]) && $trial[$target]['signature'] !== $item['signature'])
                    || ($targets === [$target] && ! isset($trial[$target]))
                    || $this->apply($item['amount'], $targets, $trial) !== 0) {
                    $invalidManual[$key] = 'Số dư hoặc dòng mua đã thay đổi. Cần kiểm tra và ghép lại.';
                    continue 2;
                }
                $available -= $item['amount'];
            }
            $lines = $trial;
            $manualApplied[$key] = $record['items'];
            if ($available > 0) {
                $unmatched[$key]['amount'] = $available;
            } else {
                unset($unmatched[$key]);
            }
        }
        $paidThrough = $firstOpen = null;
        foreach ($lines as $line) {
            if ($line['amount'] <= 0) {
                continue;
            }
            if ($line['remaining'] > 0) {
                $firstOpen = $line;
                break;
            }
            $paidThrough = $line;
        }

        return compact('lines', 'unmatched', 'credit', 'paidThrough', 'firstOpen', 'automatic', 'manualApplied', 'invalidManual', 'batchSurplus');
    }

    public function key(array $entry): string
    {
        return ($entry['source_sheet'] ?? ($entry['type'] === 'sale' ? 'nhatkybanhang' : 'Dongtienhoatdong')).':'.$entry['row'];
    }

    public function signature(array $entry): string
    {
        ksort($entry);

        return hash('sha256', json_encode($entry, JSON_THROW_ON_ERROR));
    }

    private function apply(int $amount, array $targets, array &$lines, ?array &$applied = null): int
    {
        foreach ($targets as $key) {
            if ($amount === 0) {
                break;
            }
            $paid = min($amount, $lines[$key]['remaining']);
            if ($applied !== null && $paid > 0) {
                $applied[$key] = ($applied[$key] ?? 0) + $paid;
            }
            $lines[$key]['paid'] += $paid;
            $lines[$key]['remaining'] -= $paid;
            $amount -= $paid;
        }

        return $amount;
    }

    public function hasBatchReference(string $note): bool
    {
        $normalized = Str::ascii($note);

        return preg_match('/\btheo\s+dot\b/i', $normalized) === 1 && ! preg_match('/\bbill\b/i', $normalized);
    }

    public function references(string $note): array
    {
        preg_match_all('~\bbill\s+(\d{1,2}/\d{1,2}/\d{4}|\d{4}-\d{2}-\d{2})\s*\|\s*ma\s+don\s*:\s*([A-Z0-9][A-Z0-9._/-]*)~i', Str::ascii($note), $matches, PREG_SET_ORDER);
        $references = [];
        foreach ($matches as [, $date, $code]) {
            if (str_contains($date, '/')) {
                [$day, $month, $year] = explode('/', $date);
                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
            $references[] = $date.'|'.strtoupper($code);
        }

        return array_values(array_unique($references));
    }

    private function billTargets(array $references, array $bills): array
    {
        $targets = [];
        foreach ($references as $reference) {
            if (! isset($bills[$reference])) {
                return [];
            }
            $targets = array_merge($targets, $bills[$reference]);
        }

        return $targets;
    }

    private function knownOrderCodes(Collection $sales, int $customerId): array
    {
        $missingCodes = $sales->groupBy('date')
            ->filter(fn ($rows) => $rows->every(fn ($row) => trim($row['order_code'] ?? '') === ''));
        if ($missingCodes->isEmpty()) {
            return [];
        }
        // Older Sheet rows lack an order code, so only use a unique exact amount match on the delivery date.
        $orders = Order::query()->where('customer_id', $customerId)
            ->whereDate('delivery_date', '>=', $missingCodes->keys()->min())
            ->whereDate('delivery_date', '<=', $missingCodes->keys()->max())
            ->get(['code', 'delivery_date', 'total'])
            ->groupBy(fn (Order $order) => substr((string) $order->getRawOriginal('delivery_date'), 0, 10));
        $codes = [];
        foreach ($missingCodes as $date => $rows) {
            $candidates = $orders->get($date);
            if ($candidates?->count() === 1) {
                $amount = $rows->sum('amount') - $rows->sum('gift');
                $order = $candidates->first();
                if ($order->code && (float) $order->total === (float) $amount) {
                    $codes[$date] = $order->code;
                }
            }
        }

        return $codes;
    }
}
