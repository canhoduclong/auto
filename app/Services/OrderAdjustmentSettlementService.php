<?php

namespace App\Services;

use App\Models\AccountingReconciliation;
use App\Models\Order;
use App\Models\OrderAdjustment;
use App\Support\OrderSettlement;

class OrderAdjustmentSettlementService
{
    public function sync(Order $order): void
    {
        // Also handle legacy credits on a request completed after deployment.
        $order->transactions()->where('type', 'refund')->where('method', 'return_refund')
            ->where('note', 'like', 'Refund tu don tra hang #%')
            ->whereNull('account_id')->whereNull('submitted_by')->whereNull('approved_by')
            ->whereNull('receipt_image_path')->whereNull('transfer_proof_path')
            ->whereHas('orderReturn.adjustment', fn ($query) => $query->where('status', OrderAdjustment::STATUS_COMPLETED))
            ->update(['type' => \App\Models\Transaction::TYPE_RETURN_CREDIT]);
        $returns = $order->returnRecords()
            ->whereIn('status', ['warehouse_received', 'warehouse_confirmed', 'completed'])
            ->whereDoesntHave('adjustment', fn ($query) => $query->where('status', OrderAdjustment::STATUS_COMPLETED))
            ->sum('refund_amount');
        $amounts = OrderSettlement::amounts((float) $order->total,
            (float) $order->transactions()->where('type', 'payment')->sum('amount'),
            (float) $order->transactions()->where('type', 'refund')->sum('amount'),
            (float) ($order->collected_amount ?? 0), (float) $returns);
        $reconciliation = $order->accountingReconciliation()->first();
        if ($reconciliation?->status === AccountingReconciliation::STATUS_CONFIRMED) {
            // Save reconciliation first: Order's saving hook reads its revenue.
            $reconciliation->update(['total_amount' => round((float) $order->total, 2),
                'paid_amount' => $amounts['effective_paid'], 'return_amount' => (float) $returns,
                'recognized_revenue' => $amounts['recognized']]);
        }
        $order->update(['amount_paid' => $amounts['paid'], 'amount_due' => $amounts['due'],
            'payment_status' => $amounts['status']]);
        if ($reconciliation?->status === AccountingReconciliation::STATUS_CONFIRMED) {
            app(AccountingSalesLedgerService::class)->syncOrder($order->fresh());
        }
    }
}
