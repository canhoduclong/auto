<?php

use App\Models\AccountingReconciliation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'amount_due')) {
            return;
        }

        if (!Schema::hasTable('accounting_reconciliations')) {
            DB::table('orders')->update(['amount_due' => 0]);

            return;
        }

        DB::table('orders')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('accounting_reconciliations')
                    ->whereColumn('accounting_reconciliations.order_id', 'orders.id')
                    ->where('accounting_reconciliations.status', AccountingReconciliation::STATUS_CONFIRMED);
            })
            ->update(['amount_due' => 0]);
    }

    public function down(): void
    {
        // Không thể khôi phục chính xác công nợ cũ vì dữ liệu thanh toán có thể đã thay đổi.
    }
};
