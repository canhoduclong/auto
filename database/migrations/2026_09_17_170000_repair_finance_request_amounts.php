<?php

use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transactions') || ! Schema::hasColumn('transactions', 'request_source')) {
            return;
        }

        $affectedAccountIds = collect();

        DB::table('transactions')
            ->whereNotNull('request_source')
            ->where('request_source', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($transactions) use ($affectedAccountIds): void {
                foreach ($transactions as $transaction) {
                    $items = json_decode((string) ($transaction->request_items ?? '[]'), true);
                    $items = is_array($items) ? $items : [];
                    if ($items !== []) {
                        $subtotal = round((float) collect($items)->sum(fn ($item) =>
                            (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0)
                        ), 2);
                        $vat = round((float) ($transaction->request_vat ?? 0), 2);
                        $total = round($subtotal + $vat, 2);
                    } else {
                        $total = (float) ($transaction->request_total ?? $transaction->amount ?? 0);
                        $vat = (float) ($transaction->request_vat ?? 0);
                        $subtotal = max(0, $total - $vat);
                    }

                    if ($total <= 0) {
                        continue;
                    }

                    if (abs((float) $transaction->amount - $total) >= .005
                        || abs((float) ($transaction->request_total ?? 0) - $total) >= .005) {
                        DB::table('transactions')->where('id', $transaction->id)->update([
                            'amount' => $total,
                            'request_subtotal' => $subtotal,
                            'request_vat' => $vat,
                            'request_total' => $total,
                            'updated_at' => now(),
                        ]);
                        if ($transaction->status === Transaction::STATUS_APPROVED && $transaction->account_id) {
                            $affectedAccountIds->push((int) $transaction->account_id);
                        }
                        if ($transaction->status === Transaction::STATUS_APPROVED && $transaction->destination_account_id) {
                            $affectedAccountIds->push((int) $transaction->destination_account_id);
                        }
                    }
                }
            });

        if (! Schema::hasTable('accounts')) {
            return;
        }

        $categoryFlows = Schema::hasTable('transaction_categories')
            ? DB::table('transaction_categories')->pluck('flow_direction', 'id')
            : collect();

        foreach ($affectedAccountIds->unique() as $accountId) {
            $account = DB::table('accounts')->where('id', $accountId)->first();
            if (! $account) {
                continue;
            }

            $net = 0.0;
            $transactions = DB::table('transactions')
                ->where('account_id', $accountId)
                ->where('status', Transaction::STATUS_APPROVED)
                ->get(['amount', 'type', 'transaction_category_id']);
            foreach ($transactions as $transaction) {
                $flow = $categoryFlows[$transaction->transaction_category_id] ?? null;
                $isIncome = $flow === 'in' || ($flow === null && in_array($transaction->type, ['payment', 'extra_income'], true));
                $net += $isIncome ? (float) $transaction->amount : -(float) $transaction->amount;
            }

            $internalTransfersIn = DB::table('transactions')
                ->where('destination_type', 'internal')
                ->where('destination_account_id', $accountId)
                ->where('status', Transaction::STATUS_APPROVED)
                ->get(['amount', 'type', 'transaction_category_id'])
                ->sum(function ($transaction) use ($categoryFlows): float {
                    $flow = $categoryFlows[$transaction->transaction_category_id] ?? null;
                    $isOut = $flow === 'out' || ($flow === null && ! in_array($transaction->type, ['payment', 'extra_income'], true));

                    return $isOut ? (float) $transaction->amount : 0.0;
                });

            DB::table('accounts')->where('id', $accountId)->update([
                'balance' => (float) ($account->opening_balance ?? 0) + $net + $internalTransfersIn,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Data repair is intentionally irreversible.
    }
};
