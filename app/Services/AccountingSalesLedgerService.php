<?php

namespace App\Services;

use App\Models\AccountingReconciliation;
use App\Models\AccountingSalesEntry;
use App\Models\Order;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AccountingSalesLedgerService
{
    public function syncOrder(Order $order): int
    {
        if (! Schema::hasTable('accounting_sales_entries')) {
            return 0;
        }
        $order->loadMissing(['customer', 'user', 'items.product', 'items.variant.product', 'accountingReconciliation']);
        $reconciliation = $order->accountingReconciliation;
        if (! $reconciliation || $reconciliation->status !== AccountingReconciliation::STATUS_CONFIRMED) {
            return 0;
        }

        $date = ($order->accounting_sales_import_batch_id && $order->delivery_date
            ? $order->delivery_date
            : ($reconciliation->confirmed_at ?? $order->delivered_at ?? $order->created_at))->toDateString();
        $base = [
            'entry_date' => $date,
            'entry_month' => (int) substr($date, 5, 2),
            'customer_id' => $order->customer_id,
            'customer_code' => $order->customer?->customer_code,
            'customer_name' => $order->customer?->name ?: ($order->recipient_name ?: 'Khách hàng'),
            'sale_id' => $reconciliation->sale_id ?: $order->user_id,
            'sale_name' => $reconciliation->sale?->name ?: $order->user?->name,
            'source' => AccountingSalesEntry::SOURCE_ORDER,
            'order_id' => $order->id,
            'accounting_reconciliation_id' => $reconciliation->id,
            'created_by' => $reconciliation->confirmed_by,
            'updated_by' => $reconciliation->confirmed_by,
        ];
        $keys = [];
        $lineSum = 0.0;

        foreach ($order->items as $item) {
            $key = 'order:'.$order->id.':item:'.$item->id;
            $amount = (float) ($item->total ?? 0);
            $lineSum += $amount;
            $product = $item->product ?: $item->variant?->product;
            AccountingSalesEntry::updateOrCreate(['source_key' => $key], array_merge($base, [
                'order_item_id' => $item->id,
                'unit' => $this->ledgerUnit($product?->name, $product?->unit_label),
                'quantity' => (float) ($item->quantity ?? 0),
                'unit_weight' => (float) $item->effective_unit_weight,
                'total_quantity' => (float) ($item->actual_weight ?? $item->packed_weight ?? $item->total_weight ?? $item->display_total_value),
                'unit_price' => (float) ($item->price ?? 0),
                'total_amount' => $amount,
            ]));
            $keys[] = $key;
        }

        $shippingFee = (bool) $order->charge_shipping_fee ? (float) ($order->shipping_fee ?? 0) : 0.0;
        if (abs($shippingFee) > 0.001) {
            $key = 'order:'.$order->id.':shipping-fee';
            AccountingSalesEntry::updateOrCreate(['source_key' => $key], array_merge($base, [
                'unit' => 'shiper',
                'quantity' => 1,
                'unit_weight' => 1,
                'total_quantity' => 1,
                'unit_price' => $shippingFee,
                'total_amount' => $shippingFee,
            ]));
            $lineSum += $shippingFee;
            $keys[] = $key;
        }

        $recognized = (float) $reconciliation->recognized_revenue;
        $adjustment = round($recognized - $lineSum, 2);
        if (abs($adjustment) > 0.001) {
            $key = 'order:'.$order->id.':accounting-adjustment';
            AccountingSalesEntry::updateOrCreate(['source_key' => $key], array_merge($base, [
                'unit' => $adjustment < 0 ? 'Giảm trừ' : 'Phụ phí/điều chỉnh',
                'quantity' => 1,
                'unit_weight' => 1,
                'total_quantity' => 1,
                'unit_price' => $adjustment,
                'total_amount' => $adjustment,
            ]));
            $keys[] = $key;
        }

        AccountingSalesEntry::where('order_id', $order->id)->where('source', AccountingSalesEntry::SOURCE_ORDER)
            ->when($keys, fn ($query) => $query->whereNotIn('source_key', $keys))
            ->delete();

        return count($keys);
    }

    public function syncAllConfirmed(): array
    {
        $orders = 0;
        $entries = 0;
        AccountingReconciliation::where('status', AccountingReconciliation::STATUS_CONFIRMED)
            ->with('order')
            ->chunkById(100, function ($rows) use (&$orders, &$entries): void {
                foreach ($rows as $row) {
                    if ($row->order) {
                        $entries += $this->syncOrder($row->order);
                        $orders++;
                    }
                }
            });

        return compact('orders', 'entries');
    }

    public function ledgerUnit(?string $productName, ?string $unitLabel): string
    {
        $name = trim((string) $productName);
        $normalizedName = Str::lower(Str::ascii($name));

        if (Str::contains($normalizedName, 'vit bong')) {
            return 'Con';
        }

        if ($name !== '' && ! Str::contains($normalizedName, ['vit nguyen con', 'nguyen con'])) {
            return trim((string) preg_replace('/\s+vịt.*$/iu', '', $name)) ?: ($unitLabel ?: 'Sản phẩm');
        }

        return $unitLabel ?: 'Con';
    }
}
