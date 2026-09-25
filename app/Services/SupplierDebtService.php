<?php

namespace App\Services;

use App\Models\ProcurementPurchase;
use App\Models\SupplierDebtPayment;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierDebtService
{
    public function recordApprovedTransaction(Transaction $transaction): ?SupplierDebtPayment
    {
        if ($transaction->status !== Transaction::STATUS_APPROVED) {
            return null;
        }

        $purchase = ProcurementPurchase::query()
            ->where('payment_transaction_id', $transaction->id)
            ->lockForUpdate()
            ->first();

        if (! $purchase) {
            return null;
        }

        $existing = SupplierDebtPayment::query()->where('transaction_id', $transaction->id)->first();
        if ($existing) {
            $this->recalculate($purchase);
            return $existing;
        }

        $amount = (float) $transaction->amount;
        if ($amount > (float) $purchase->remaining_amount + 0.01) {
            throw ValidationException::withMessages([
                'amount' => 'Số tiền trên phiếu lớn hơn công nợ còn lại của lần mua '.$purchase->code.'.',
            ]);
        }

        $payment = SupplierDebtPayment::create([
            'procurement_purchase_id' => $purchase->id,
            'transaction_id' => $transaction->id,
            'amount' => $amount,
            'paid_at' => now(),
            'recorded_by' => auth()->id(),
            'note' => 'Thanh toán theo phiếu #'.$transaction->id,
        ]);

        $this->recalculate($purchase);

        return $payment;
    }

    public function recordPayment(
        ProcurementPurchase $purchase,
        float $amount,
        $paidAt,
        ?int $recordedBy = null,
        ?string $note = null,
        ?int $transactionId = null,
    ): SupplierDebtPayment {
        return DB::transaction(function () use ($purchase, $amount, $paidAt, $recordedBy, $note, $transactionId) {
            $purchase = ProcurementPurchase::query()->lockForUpdate()->findOrFail($purchase->id);

            if ($amount <= 0 || $amount > (float) $purchase->remaining_amount + 0.01) {
                throw ValidationException::withMessages([
                    'amount' => 'Số tiền thanh toán phải lớn hơn 0 và không vượt quá công nợ còn lại.',
                ]);
            }

            $payment = SupplierDebtPayment::create([
                'procurement_purchase_id' => $purchase->id,
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'paid_at' => $paidAt,
                'recorded_by' => $recordedBy,
                'note' => $note,
            ]);

            $this->recalculate($purchase);

            return $payment;
        });
    }

    public function recordOpeningPayment(ProcurementPurchase $purchase, float $amount, ?int $recordedBy = null): void
    {
        if ($amount <= 0 || $purchase->debtPayments()->exists()) {
            return;
        }

        SupplierDebtPayment::create([
            'procurement_purchase_id' => $purchase->id,
            'amount' => $amount,
            'paid_at' => $purchase->purchased_at ?? now(),
            'recorded_by' => $recordedBy,
            'note' => 'Số tiền đã trả khi ghi nhận lần mua',
        ]);
    }

    public function recalculate(ProcurementPurchase $purchase): void
    {
        $paid = (float) $purchase->debtPayments()->sum('amount');
        $remaining = max(0, (float) $purchase->total_amount - $paid);

        $purchase->forceFill([
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
            'payment_status' => $remaining <= 0.01 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
        ])->save();
    }
}
