<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderAdjustment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairZeroQuantityOrder extends Command
{
    protected $signature = 'orders:repair-zero-quantity {code : Mã đơn cần sửa} {--apply : Lưu kết quả sửa}';

    protected $description = 'Sửa thành tiền còn sót trên sản phẩm đã điều chỉnh về số lượng 0';

    public function handle(): int
    {
        return DB::transaction(function (): int {
            $order = Order::where('code', $this->argument('code'))->lockForUpdate()->first();
            if (! $order) {
                $this->error('Không tìm thấy đơn.');
                return self::FAILURE;
            }
            $order->load(['items.product', 'items.variant.product', 'additionalFees']);
            $completedItemIds = $order->adjustments()->where('status', OrderAdjustment::STATUS_COMPLETED)
                ->with('items')->get()->flatMap->items->where('adjusted_quantity', 0)->pluck('order_item_id');
            $items = $order->items->filter(fn ($item) => (int) $item->quantity === 0
                && (float) $item->total > 0 && $completedItemIds->contains($item->id));
            if ($items->isEmpty()) {
                $this->info('Không có dòng lỗi cần sửa.');
                return self::SUCCESS;
            }
            $removed = (float) $items->sum('total');
            $subtotal = max(0, (float) $order->items->sum('total') - $removed);
            $productTotal = max(0, $subtotal - (float) $order->extra_discount_total);
            $vat = $order->resolvedVatAmount($productTotal);
            $feeDelta = 0.0;
            foreach ($order->additionalFees as $fee) {
                if ($fee->calculation_type !== 'percent') {
                    continue;
                }
                $newAmount = round($productTotal * min(max((float) $fee->rate, 0), 100) / 100, 2);
                $feeDelta += ($newAmount - (float) $fee->amount) * ($fee->direction === 'discount' ? -1 : 1);
                if ($this->option('apply')) {
                    $fee->update(['base_amount' => $productTotal, 'amount' => $newAmount]);
                }
            }
            $total = max(0, round((float) $order->total - $removed + $vat - (float) $order->vat_amount + $feeDelta, 2));
            $reduction = (float) $order->total - $total;
            $this->info($order->code.': '.$order->total.' → '.$total.' (loại tiền sản phẩm: '.$removed.')');
            if (! $this->option('apply')) {
                $this->info('Chỉ kiểm tra. Thêm --apply để lưu.');
                return self::SUCCESS;
            }
            foreach ($items as $item) {
                $item->update(['total' => 0, 'total_weight' => 0, 'actual_weight' => 0, 'packed_weight' => 0, 'is_priced_by_kg' => $item->effective_priced_by_kg]);
            }
            $due = max(0, round((float) $order->amount_due - $reduction, 2));
            $order->update(['subtotal_amount' => round($subtotal, 2), 'total' => $total, 'vat_amount' => $vat,
                'amount_due' => $due, 'payment_status' => $due <= 0 ? 'paid'
                    : ((float) $order->amount_paid > 0 || (float) $order->collected_amount > 0 ? 'partially_paid' : 'unpaid')]);
            $reconciliation = $order->accountingReconciliation()->first();
            if ($reconciliation?->status === \App\Models\AccountingReconciliation::STATUS_CONFIRMED) {
                $reconciliation->update(['total_amount' => $total,
                    'recognized_revenue' => max(0, round((float) $reconciliation->recognized_revenue - $reduction, 2))]);
                app(\App\Services\AccountingSalesLedgerService::class)->syncOrder($order->fresh());
            }
            $this->info('Đã lưu. Giữ nguyên lịch sử thanh toán và hồ sơ điều chỉnh.');
            return self::SUCCESS;
        });
    }
}
