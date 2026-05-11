<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->decimal('opening_balance', 15, 2)->default(0)->after('balance')
                  ->comment('Số dư ban đầu + nạp/rút thủ công. Không tính giao dịch.');
        });

        // Set opening_balance = current_balance - sum(approved in txns) + sum(approved out txns)
        // This preserves the current balance as correct when reconcile runs.
        $accounts = DB::table('accounts')->get(['id', 'balance']);
        foreach ($accounts as $acc) {
            $transactions = DB::table('transactions as t')
                ->leftJoin('transaction_categories as tc', 'tc.id', '=', 't.transaction_category_id')
                ->where('t.account_id', $acc->id)
                ->where('t.status', 'approved')
                ->selectRaw("
                    COALESCE(SUM(CASE
                        WHEN COALESCE(tc.flow_direction,
                            CASE WHEN t.type IN ('payment','extra_income') THEN 'in' ELSE 'out' END
                        ) = 'in' THEN t.amount ELSE 0 END), 0) as total_in,
                    COALESCE(SUM(CASE
                        WHEN COALESCE(tc.flow_direction,
                            CASE WHEN t.type IN ('payment','extra_income') THEN 'in' ELSE 'out' END
                        ) = 'out' THEN t.amount ELSE 0 END), 0) as total_out
                ")
                ->first();

            $txnNet = (float) $transactions->total_in - (float) $transactions->total_out;
            $openingBalance = (float) $acc->balance - $txnNet;

            DB::table('accounts')->where('id', $acc->id)->update([
                'opening_balance' => $openingBalance,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('opening_balance');
        });
    }
};
