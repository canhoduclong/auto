<?php

namespace App\Services;

use Illuminate\Support\Collection;

class CustomerDebtBillReconciliation
{
    public function __construct(private CustomerDebtPaymentAllocation $allocation) {}

    public function exactPairs(array $period): array
    {
        $bills = $period['bills']->where('remaining', '>', 0)
            ->groupBy(fn ($bill) => $bill['date'].'|'.$bill['remaining'], true);
        // Count all pending receipts so that a saved partial allocation cannot hide ambiguity.
        $payments = $period['payments']->where('pending', '>', 0)
            ->groupBy(fn ($payment) => $payment['date'].'|'.$payment['pending'], true);
        $pairs = [];
        foreach ($payments as $key => $receipts) {
            $candidates = $bills->get($key);
            if ($candidates?->count() === 1 && $receipts->count() === 1 && $receipts->first()['can_allocate'] && ! $receipts->first()['confirmed'] && $receipts->first()['allocated'] === 0 && $candidates->first()['paid'] === 0 && ! $candidates->first()['in_batch'] && ! $receipts->first()['in_batch']) {
                $pairs[$receipts->keys()->first()] = $candidates->keys()->first();
            }
        }

        return $pairs;
    }

    public function periods(array $entries, array $policy, array $result, array $records): array
    {
        $entries = collect($entries)->sortBy([['date', 'asc'], ['row', 'asc']]);
        $today = today()->toDateString();
        $confirmedKeys = collect($records)->filter(fn ($record) => isset($record['confirmed_group']))
            ->mapWithKeys(fn ($record, $key) => [($record['payment_key'] ?? $key) => true]);
        $processedBills = $processedPayments = [];
        foreach (array_intersect_key($records, $result['confirmedApplied']) as $record) {
            $batch = $record['batch'] ?? [];
            foreach ($batch['bill_keys'] ?? [] as $key) {
                $processedBills[$batch['period_from']][$key] = true;
            }
            foreach ($batch['payment_keys'] ?? [] as $key) {
                $processedPayments[$key] = true;
            }
        }
        $periods = [];
        foreach (CustomerDebtPolicyService::settlementPeriods($policy, $entries->first()['date'] ?? null) as $period) {
            if ($period['method'] !== 'bill' || $period['from'] === null || $period['from'] > $today) {
                continue;
            }
            $rows = $entries->whereBetween('date', [$period['from'], min($period['to'] ?? $today, $today)]);
            $lines = $rows->where('type', 'sale')->mapWithKeys(function ($entry) use ($result) {
                $key = $this->allocation->key($entry);

                return [$key => $result['lines'][$key]];
            })->where('amount', '>', 0);
            $bills = $lines->groupBy(fn ($line, $key) => $line['code'] === ''
                ? $key
                : $line['date'].'|'.mb_strtoupper($line['code']), preserveKeys: true)
                ->map(fn (Collection $bill, $key) => [
                    'label' => $bill->first()['code'] ?: 'Dòng mua '.$bill->first()['row'],
                    'date' => $bill->first()['date'],
                    'amount' => $bill->sum('amount'),
                    'paid' => $bill->sum('paid'),
                    'remaining' => $bill->sum('remaining'),
                    'in_batch' => isset($processedBills[$period['from']][$key]),
                    'lines' => $bill->where('remaining', '>', 0),
                ]);
            $payments = $rows->where('type', 'payment')->keyBy(fn ($entry) => $this->allocation->key($entry))
                ->map(function ($entry, $key) use ($result, $records, $confirmedKeys, $processedPayments) {
                    $pending = $result['unmatched'][$key]['amount'] ?? 0;
                    $issue = $result['invalidManual'][$key] ?? $result['unmatched'][$key]['reason'] ?? null;
                    $hasBillReference = $this->allocation->references($entry['note'] ?? '') !== []
                        || (($entry['source_sheet'] ?? '') === 'nhatkybanhang' && isset($result['lines'][$key]));
                    $allocations = $hasBillReference ? ($result['automatic'][$key] ?? []) : [];
                    foreach ($result['manualApplied'][$key] ?? [] as $item) {
                        $target = $item['target'];
                        $allocations[$target] = ($allocations[$target] ?? 0) + $item['amount'];
                    }

                    $matched = $entry['amount'] > 0 && $pending === 0 && $issue === null
                        && array_sum($allocations) === $entry['amount']
                        && array_diff_key($allocations, $result['lines']) === [];
                    $labels = $matched ? array_map(function ($target) use ($result) {
                        $line = $result['lines'][$target];

                        return $line['code'] ?: 'Dòng mua '.$line['row'];
                    }, array_keys($allocations)) : [];

                    return $entry + [
                        'pending' => $pending,
                        'issue' => $issue,
                        'saved' => isset($records[$key]),
                        'confirmed' => $confirmedKeys->has($key),
                        'allocated' => array_sum($allocations),
                        'in_batch' => isset($processedPayments[$key]),
                        'can_allocate' => $pending > 0 && ! isset($result['invalidManual'][$key])
                            && (! isset($records[$key]) || isset($records[$key]['confirmed_group'])),
                        'matched' => $matched,
                        'matched_bills' => array_unique($labels),
                    ];
                });
            $periods[$period['from']] = $period + [
                'bills' => $bills, 'payments' => $payments,
                'closing' => $entries->where('date', '<=', min($period['to'] ?? $today, $today))
                    ->sum(fn ($entry) => app(CustomerDebtSheetService::class)->movement($entry)),
            ];
            $periods[$period['from']]['exact_pairs'] = $this->exactPairs($periods[$period['from']]);
        }

        return $periods;
    }
}
