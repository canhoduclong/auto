<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderHistory;
use Illuminate\Support\Collection;

class WarehouseSaleChangeService
{
    public const CHANGED = 'sale_changed_after_packing';
    public const CONFIRMED = 'warehouse_confirmed_sale_changes';

    public function shouldTrack(Order $order): bool
    {
        $user = auth()->user();
        if (! $user?->isSalesFlowRole() || request()->routeIs('warehouse.*', 'package.*', 'shipper.*')) {
            return false;
        }

        return in_array((string) $order->getRawOriginal('status'), [Order::STATUS_PACKING, Order::STATUS_PACKED, Order::STATUS_READY_TO_SHIP], true)
            || $order->histories()->whereIn('action', ['start_packing', 'warehouse_confirm_pack', 'complete_packing', 'warehouse_complete_packing'])->exists();
    }

    public function record(Order $order, string $note): void
    {
        OrderHistory::create([
            'order_id' => $order->id, 'action' => self::CHANGED,
            'user_id' => auth()->id(), 'role' => 'sale',
            'status_before' => $order->getRawOriginal('status'), 'status_after' => $order->status,
            'note' => $note,
        ]);
    }

    public function recordOrderChanges(Order $order): void
    {
        $labels = [
            'customer_id' => 'Khách hàng (mã)', 'user_id' => 'Sale phụ trách (mã)',
            'vat_percent' => 'VAT (%)', 'total_discount' => 'Tổng giảm giá',
            'extra_discount_total' => 'Chiết khấu', 'order_discount_type' => 'Loại giảm giá',
            'recipient_name' => 'Người nhận', 'recipient_phone' => 'Điện thoại',
            'recipient_address' => 'Địa chỉ', 'delivery_date' => 'Ngày giao',
            'delivery_time' => 'Giờ giao', 'note' => 'Ghi chú', 'shipper_note' => 'Ghi chú giao hàng',
            'shipping_fee' => 'Phí giao hàng', 'customer_shipping_fee' => 'Phí ship thu khách',
            'foam_box_price' => 'Phí thùng xốp', 'vat_amount' => 'Tiền VAT',
            'order_discount' => 'Giảm giá', 'total' => 'Tổng tiền',
            'charge_shipping_fee' => 'Tính phí giao hàng', 'charge_foam_box_fee' => 'Tính phí thùng xốp',
            'charge_vat' => 'Tính VAT', 'collect_customer_shipping_fee' => 'Thu phí ship khách',
            'packing_specification' => 'Quy cách đóng', 'package_count' => 'Số bọc',
        ];
        $changes = array_intersect_key($order->getChanges(), $labels);
        if ($changes === [] || ! $this->shouldTrack($order)) {
            return;
        }
        $notes = [];
        foreach ($changes as $field => $value) {
            $notes[] = $labels[$field].': '.($order->getRawOriginal($field) ?? 'Trống').' → '.($value ?? 'Trống');
        }
        $this->record($order, implode('; ', $notes));
    }

    public function itemSummary(Order $order): string
    {
        return $order->items()->with('variant')->get()->map(fn ($item) =>
            ($item->variant?->name ?: '#'.$item->product_variant_id).' × '.$item->quantity.'; giá '.number_format((float) $item->price).'đ'
        )->sort()->implode(' | ');
    }

    public function attach(Collection $orders): void
    {
        foreach ($orders as $order) {
            $changes = $order->histories->where('action', self::CHANGED)->sortBy('id');
            $through = $order->histories->where('action', self::CONFIRMED)
                ->max(fn ($history) => (int) $history->schedule_snapshot_hash) ?? 0;
            $pending = $changes->filter(fn ($history) => $history->id > $through)->values();
            $order->setAttribute('sale_changes_pending', $pending);
            $order->setAttribute('sale_changes_latest_id', (int) ($changes->last()?->id ?? 0));
            $order->setAttribute('sale_changes_confirmed', $changes->isNotEmpty() && $pending->isEmpty());
        }
    }
}
