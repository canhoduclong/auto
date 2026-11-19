<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CustomerDebtPaymentAllocation
{
    public function reconcile(array $entries, string $method, ?int $customerId = null): array
    {
        $sales = collect($entries)->where('type', 'sale')->sortBy([['date', 'asc'], ['row', 'asc']]);
        $payments = collect($entries)->where('type', 'payment')->sortBy([['date', 'asc'], ['row', 'asc']]);
        $systemCodes = $method !== 'batch' && $customerId ? $this->knownOrderCodes($sales, $customerId) : [];
        $lines = $bills = [];
        foreach ($sales as $sale) {
            $key = $this->key($sale);
            $code = trim($sale['order_code'] ?? '') ?: ($systemCodes[$sale['date']] ?? '');
            $amount = max(0, $sale['amount'] - ($sale['gift'] ?? 0));
            $lines[$key] = ['date' => $sale['date'], 'row' => $sale['row'], 'code' => $code, 'amount' => $amount, 'paid' => 0, 'remaining' => $amount];
            if ($code !== '') {
                $bills[$sale['date'].'|'.mb_strtoupper($code)][] = $key;
            }
        }

        $unmatched = [];
        $batch = 0;
        foreach ($payments as $payment) {
            $key = $this->key($payment);
            $amount = $payment['amount'];
            if ($amount === 0) {
                continue;
            }
            if ($method === 'batch') {
                $batch += $amount;
                continue;
            }
            $note = $payment['note'] ?? '';
            $references = $this->references($note);
            if ($method === 'mixed' && $references === [] && $this->hasBatchReference($note)) {
                $batch += $amount;
                continue;
            }
            $targets = ($payment['source_sheet'] ?? '') === 'nhatkybanhang' && isset($lines[$key])
                ? [$key]
                : $this->billTargets($references, $bills);
            if ($amount < 0 || $targets === []) {
                $unmatched[$key] = ['amount' => $amount, 'reason' => $amount < 0 ? 'Khoản thu âm cần đối chiếu' : 'Thiếu hoặc không khớp ngày giao và mã Bill'];
                continue;
            }

            $left = $this->apply($amount, $targets, $lines);
            if ($left > 0) {
                $unmatched[$key] = ['amount' => $left, 'reason' => 'Số thu vượt phần còn phải thu của Bill đã ghi'];
            }
        }

        $credit = $batch > 0 ? $this->apply($batch, array_keys($lines), $lines) : $batch;
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

        return compact('lines', 'unmatched', 'credit', 'paidThrough', 'firstOpen');
    }

    public function key(array $entry): string
    {
        return ($entry['source_sheet'] ?? ($entry['type'] === 'sale' ? 'nhatkybanhang' : 'Dongtienhoatdong')).':'.$entry['row'];
    }

    private function apply(int $amount, array $targets, array &$lines): int
    {
        foreach ($targets as $key) {
            if ($amount === 0) {
                break;
            }
            $paid = min($amount, $lines[$key]['remaining']);
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
        if ($sales->isEmpty()) {
            return [];
        }
        // Older Sheet rows lack an order code, so only use a unique exact amount match on the delivery date.
        $orders = Order::query()->where('customer_id', $customerId)
            ->whereDate('delivery_date', '>=', $sales->min('date'))
            ->whereDate('delivery_date', '<=', $sales->max('date'))
            ->get(['code', 'delivery_date', 'total'])
            ->groupBy(fn (Order $order) => substr((string) $order->getRawOriginal('delivery_date'), 0, 10));
        $codes = [];
        foreach ($sales->groupBy('date') as $date => $rows) {
            $candidates = $orders->get($date);
            if ($candidates?->count() === 1 && $rows->every(fn ($row) => trim($row['order_code'] ?? '') === '')) {
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
