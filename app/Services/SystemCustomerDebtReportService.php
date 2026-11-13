<?php

namespace App\Services;

use App\Models\Customer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SystemCustomerDebtReportService
{
    public function rows(): Collection
    {
        $customers = Customer::query()->with(['currentOwner:id,name,short_name', 'assignedTo:id,name,short_name'])->get();
        $policyService = app(CustomerDebtPolicyService::class);
        $policies = $policyService->policies();
        $recoveryService = app(CustomerDebtRecoveryService::class);
        $recoveries = $recoveryService->records();
        $rows = [];
        foreach ($customers as $customer) {
            $owner = $customer->currentOwner ?? $customer->assignedTo;
            $key = hash('sha256', 'system-customer:'.$customer->id);
            $row = ['key' => $key, 'source_keys' => [$key], 'identity' => 'customer_'.$customer->id, 'customer_id' => $customer->id, 'system_customer' => $customer, 'name' => $customer->name, 'code' => $customer->customer_code ?? '', 'sales' => 0, 'payments' => 0, 'gifts' => 0, 'adjustment_total' => 0, 'balance' => 0, 'entries' => [], 'last_purchase_date' => null, 'last_payment_date' => null, 'payment_date_unknown' => false, 'data_available' => true, 'report_source' => 'system', 'sale_key' => $owner ? 'user:'.$owner->id : 'unassigned', 'sale_label' => $owner ? (trim((string) $owner->short_name) ?: $owner->name) : 'Chưa phân công', 'search_text' => $customer->name.' '.$customer->customer_code];
            $row['recovery'] = $recoveryService->effective($row, $recoveries);
            $row['policy'] = $policyService->effective($row, $policies);
            $rows[$customer->id] = $row;
        }
        // Only confirmed reconciliation creates a system receivable, matching the accounting workflow.
        $orders = DB::table('orders as o')->join('accounting_reconciliations as r', 'r.order_id', '=', 'o.id')->where('r.status', 'confirmed')->whereNotIn('o.status', ['draft', 'cancelled', 'rejected'])->whereIn('o.customer_id', array_keys($rows))->orderBy('r.id')
            ->get(['o.id', 'o.customer_id', 'o.code', 'o.created_at', 'o.delivery_date', 'o.amount_paid', 'o.collected_amount', 'r.recognized_revenue', 'r.paid_amount', 'r.confirmed_at'])->keyBy('id');
        $transactions = DB::table('transactions as t')->leftJoin('orders as o', 'o.id', '=', 't.order_id')
            ->where(fn ($q) => $q->where('t.status', 'approved')->orWhereNull('t.status'))
            ->whereIn('t.type', ['payment', 'refund', 'customer_opening_debt', 'customer_debt_adjustment'])
            ->where(fn ($q) => $q->whereNull('t.order_id')->orWhereIn('t.order_id', $orders->keys()->all()))
            ->select(['t.*'])->selectRaw('CASE WHEN t.order_id IS NULL THEN t.customer_id ELSE o.customer_id END AS resolved_customer_id')->orderBy('t.id')->get();
        $paidByOrder = [];
        foreach ($orders as $order) {
            $date = CarbonImmutable::parse($order->delivery_date ?: $order->created_at)->toDateString();
            $amount = (int) round((float) $order->recognized_revenue);
            $row = &$rows[$order->customer_id];
            $row['sales'] += $amount;
            $row['entries'][] = ['date' => $date, 'type' => 'sale', 'amount' => $amount, 'gift' => 0, 'code' => $row['code'], 'name' => $row['name'], 'row' => (int) $order->id, 'note' => 'Đơn '.($order->code ?: '#'.$order->id), 'source_sheet' => 'system'];
            if ($amount > 0) {
                $row['last_purchase_date'] = max($row['last_purchase_date'] ?? '', $date);
            }
            unset($row);
        }
        foreach ($transactions as $transaction) {
            $id = (int) $transaction->resolved_customer_id;
            if (! isset($rows[$id])) {
                continue;
            }
            $at = $transaction->approved_at ?: $transaction->created_at;
            if (! $at) {
                continue;
            }$date = CarbonImmutable::parse($at)->toDateString();
            if ($date > now()->toDateString()) {
                continue;
            }
            $row = &$rows[$id];
            $adjustment = in_array($transaction->type, ['customer_opening_debt', 'customer_debt_adjustment'], true);
            $amount = (int) round((float) $transaction->amount) * ($transaction->type === 'refund' ? -1 : 1);
            $row[$adjustment ? 'adjustment_total' : 'payments'] += $amount;
            if (! $adjustment && $transaction->order_id) {
                $paidByOrder[$transaction->order_id] = ($paidByOrder[$transaction->order_id] ?? 0) + $amount;
            }
            $row['entries'][] = ['date' => $date, 'occurred_at' => CarbonImmutable::parse($at)->toDateTimeString(), 'type' => $adjustment ? 'adjustment' : 'payment', 'amount' => $amount, 'gift' => 0, 'code' => $row['code'], 'name' => $row['name'], 'row' => (int) $transaction->id, 'transaction_id' => (int) $transaction->id, 'note' => $transaction->note ?: ($adjustment ? 'Điều chỉnh công nợ hệ thống' : 'Phiếu thanh toán #'.$transaction->id), 'source_sheet' => 'system'];
            if (! $adjustment && $amount > 0) {
                $row['last_payment_date'] = max($row['last_payment_date'] ?? '', $date);
            }
            unset($row);
        }
        foreach ($orders as $order) {
            // Accounting/shipper fields are cumulative totals, never extra receipts to add to transactions.
            $transactionPaid = $paidByOrder[$order->id] ?? 0;
            $effectivePaid = max(0, (int) round((float) $order->amount_paid), (int) round((float) $order->collected_amount), (int) round((float) $order->paid_amount), $transactionPaid);
            $unitemized = $effectivePaid - $transactionPaid;
            if ($unitemized <= 0) {
                continue;
            }
            $row = &$rows[$order->customer_id];
            $row['payments'] += $unitemized;
            $row['payment_date_unknown'] = true;
            $row['entries'][] = ['date' => CarbonImmutable::parse($order->confirmed_at ?: $order->created_at)->toDateString(), 'type' => 'payment_summary', 'amount' => $unitemized, 'gift' => 0, 'code' => $row['code'], 'name' => $row['name'], 'row' => (int) $order->id, 'note' => 'Số đã thu tổng hợp của đơn '.($order->code ?: '#'.$order->id).' chưa có phiếu thu chi tiết', 'source_sheet' => 'system'];
            unset($row);
        }
        foreach ($rows as &$row) {
            $row['balance'] = $row['sales'] - $row['payments'] + $row['adjustment_total'];
            $row['display_balance'] = $row['balance'];
            $row['balance_target_name'] = $row['name'];
            foreach (['last_purchase_date' => 'days_without_purchase', 'last_payment_date' => 'days_without_payment'] as $field => $days) {
                $row[$days] = $row[$field] ? max(0, (int) CarbonImmutable::parse($row[$field])->diffInDays(CarbonImmutable::today())) : null;
            }
            if ($row['payment_date_unknown']) {
                $row['days_without_payment'] = null;
            }
            $policyEntries = array_map(fn ($entry) => $entry['type'] === 'payment_summary' ? array_replace($entry, ['type' => 'adjustment', 'amount' => -$entry['amount']]) : $entry, $row['entries']);
            $row['alerts'] = $policyService->evaluate($policyEntries, $row['policy']);
        }unset($row);

        return collect($rows)->keyBy('identity');
    }
}
