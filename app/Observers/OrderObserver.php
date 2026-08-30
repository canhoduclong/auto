<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\User;
use App\Services\AdminActivityService;
use App\Services\GoogleSheetsJournalSyncScheduler;
use App\Services\GoogleSheetsOrderSyncScheduler;
use Carbon\Carbon;

class OrderObserver
{
    public function __construct(
        private readonly GoogleSheetsJournalSyncScheduler $journalSync,
        private readonly GoogleSheetsOrderSyncScheduler $orderSheetSync,
    ) {}

    public function created(Order $order): void
    {
        AdminActivityService::record(
            'order',
            'created',
            $order,
            'Tao moi don hang',
            'Don hang "'.($order->code ?: ('#'.$order->id)).'" vua duoc tao.',
            ['order_id' => $order->id, 'code' => $order->code, 'status' => $order->status],
            route('orders.show', $order)
        );

        $this->journalSync->scheduleDates([$order->created_at]);
        if (in_array((string) $order->status, [Order::STATUS_APPROVED, Order::STATUS_CANCELLED], true)) {
            $this->orderSheetSync->schedule([$order->id]);
        }
    }

    public function updated(Order $order): void
    {
        $action = $order->wasChanged('status') ? 'status_changed' : 'updated';
        $order->loadMissing(['customer:id,name', 'user:id,name,short_name']);
        $customerName = trim((string) ($order->customer?->name ?: $order->recipient_name ?: 'Chưa xác định'));
        $saleName = trim((string) ($order->user?->short_name ?: $order->user?->name ?: 'Chưa phân công'));
        $changes = $this->meaningfulChanges($order);
        $changeSummary = $this->changeSummary($order, $changes);

        AdminActivityService::record(
            'order',
            $action,
            $order,
            'Cập nhật đơn · '.$customerName,
            'Đơn '.($order->code ?: ('#'.$order->id)).' của '.$customerName
                .' · Sale: '.$saleName.'. Thay đổi: '.$changeSummary.'.',
            [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'customer_name' => $customerName,
                'sale_name' => $saleName,
                'changes' => $changes,
                'change_summary' => $changeSummary,
            ],
            route('orders.show', $order)
        );

        $this->journalSync->scheduleDates([
            $order->created_at,
            $order->getRawOriginal('created_at'),
        ]);
        if ($order->wasChanged('status')
            && in_array((string) $order->status, [Order::STATUS_APPROVED, Order::STATUS_CANCELLED], true)) {
            $this->orderSheetSync->schedule([$order->id]);
        }
    }

    /**
     * Loại bỏ các cột kỹ thuật không hữu ích đối với người đọc thông báo.
     *
     * @return array<string, mixed>
     */
    private function meaningfulChanges(Order $order): array
    {
        return collect($order->getChanges())
            ->except(['updated_at'])
            ->all();
    }

    /** @param array<string, mixed> $changes */
    private function changeSummary(Order $order, array $changes): string
    {
        if ($changes === []) {
            return 'Dữ liệu đơn hàng được đồng bộ';
        }

        $summaries = collect($changes)
            ->map(function (mixed $newValue, string $field) use ($order): string {
                $oldValue = $order->getRawOriginal($field);

                return $this->fieldLabel($field).': '
                    .$this->formatValue($field, $oldValue).' → '
                    .$this->formatValue($field, $newValue);
            })
            ->values();

        $visible = $summaries->take(6)->implode('; ');
        $remaining = $summaries->count() - 6;

        return $remaining > 0 ? $visible.'; và '.$remaining.' thay đổi khác' : $visible;
    }

    private function fieldLabel(string $field): string
    {
        return [
            'status' => 'Trạng thái',
            'customer_id' => 'Khách hàng',
            'user_id' => 'Sale phụ trách',
            'shipper_id' => 'Shipper',
            'recipient_name' => 'Người nhận',
            'recipient_phone' => 'SĐT người nhận',
            'recipient_email' => 'Email người nhận',
            'recipient_address' => 'Địa chỉ giao',
            'delivery_date' => 'Ngày giao',
            'delivery_time' => 'Giờ giao',
            'note' => 'Ghi chú',
            'shipper_note' => 'Ghi chú shipper',
            'total' => 'Tổng tiền',
            'subtotal_amount' => 'Tiền hàng',
            'total_discount' => 'Tổng giảm trừ',
            'extra_discount_total' => 'Chiết khấu',
            'order_discount' => 'Giảm giá đơn',
            'shipping_fee' => 'Phí giao hàng',
            'customer_shipping_fee' => 'Phí ship thu khách',
            'foam_box_price' => 'Phí thùng xốp',
            'vat_percent' => 'VAT',
            'vat_amount' => 'Tiền VAT',
            'amount_paid' => 'Đã thanh toán',
            'amount_due' => 'Còn phải thu',
            'payment_method' => 'Phương thức thanh toán',
            'payment_status' => 'Trạng thái thanh toán',
            'total_weight' => 'Tổng khối lượng',
            'actual_weight' => 'Khối lượng thực tế',
            'package_count' => 'Số bọc',
            'packing_specification' => 'Quy cách bọc',
            'charge_shipping_fee' => 'Tính phí giao hàng',
            'collect_customer_shipping_fee' => 'Thu phí ship khách',
            'charge_foam_box_fee' => 'Tính phí thùng xốp',
            'charge_vat' => 'Tính VAT',
            'warehouse_can_adjust' => 'Kho được điều chỉnh',
            'warehouse_id' => 'Kho xử lý',
            'return_warehouse_id' => 'Kho trả hàng',
            'daily_sequence' => 'Số thứ tự ngày',
        ][$field] ?? str_replace('_', ' ', ucfirst($field));
    }

    private function formatValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'Trống';
        }

        if ($field === 'status') {
            return $this->statusLabel((string) $value);
        }

        if ($field === 'customer_id') {
            return Customer::query()->whereKey($value)->value('name') ?: '#'.$value;
        }

        if (in_array($field, ['user_id', 'shipper_id'], true)) {
            $user = User::query()->select(['name', 'short_name'])->find($value);

            return $user?->short_name ?: $user?->name ?: '#'.$value;
        }

        if (in_array($field, [
            'total', 'subtotal_amount', 'total_discount', 'extra_discount_total',
            'order_discount', 'shipping_fee', 'customer_shipping_fee', 'foam_box_price',
            'vat_amount', 'amount_paid', 'amount_due',
        ], true)) {
            return number_format((float) $value, 0, ',', '.').'đ';
        }

        if (in_array($field, ['total_weight', 'actual_weight'], true)) {
            return rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',').' kg';
        }

        if ($field === 'package_count') {
            return number_format((int) $value).' bọc';
        }

        if ($field === 'vat_percent') {
            return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',').'%';
        }

        if (in_array($field, [
            'charge_shipping_fee', 'collect_customer_shipping_fee', 'charge_foam_box_fee',
            'charge_vat', 'warehouse_can_adjust', 'stock_sufficient',
        ], true)) {
            return filter_var($value, FILTER_VALIDATE_BOOL) ? 'Có' : 'Không';
        }

        if ($field === 'delivery_date') {
            try {
                return Carbon::parse($value)->format('d/m/Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        }

        return trim((string) $value) ?: 'Trống';
    }

    private function statusLabel(string $status): string
    {
        return (Order::statusOptions() + [
            Order::STATUS_PENDING_LEADER_APPROVAL => 'Chờ Leader duyệt',
            Order::STATUS_PENDING_MANAGER_APPROVAL => 'Chờ Manager duyệt',
            'pending_warehouse_approval' => 'Chờ Kho duyệt',
            Order::STATUS_APPROVED => 'Đã duyệt',
            Order::STATUS_READY_TO_PACK => 'Chờ đóng gói',
            Order::STATUS_PACKING => 'Đang đóng gói',
            Order::STATUS_READY_TO_SHIP => 'Chờ vận chuyển',
            Order::STATUS_DELIVERING => 'Đang vận chuyển',
            Order::STATUS_RETURNING => 'Đang trả hàng',
            Order::STATUS_RETURNED_COMPLETED => 'Đã nhập kho trả hàng',
        ])[$status] ?? $status;
    }

    public function deleted(Order $order): void
    {
        AdminActivityService::record(
            'order',
            'deleted',
            $order,
            'Xoa don hang',
            'Don hang "'.($order->code ?: ('#'.$order->id)).'" da bi xoa.',
            ['order_id' => $order->id, 'code' => $order->code],
            route('orders.index')
        );

        $this->journalSync->scheduleDates([$order->created_at]);
    }
}
