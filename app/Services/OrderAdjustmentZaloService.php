<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderAdjustment;

class OrderAdjustmentZaloService
{
    public function text(OrderAdjustment $adjustment, Order $order, string $url): string
    {
        $number = static fn ($value): string => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
        $money = static fn ($value): string => number_format((float) $value, 0, ',', '.').'đ';
        $lines = [
            'YÊU CẦU ĐIỀU CHỈNH #'.$adjustment->id,
            'Đơn: '.$order->code,
            'Khách hàng: '.($order->customer?->name ?? '—'),
            'Sale: '.($order->user?->short_name ?: $order->user?->name ?: '—'),
            'Người yêu cầu: '.($adjustment->requester?->short_name ?: $adjustment->requester?->name ?: '—'),
            'Ngày gửi: '.optional($adjustment->submitted_at ?? $adjustment->created_at)->format('d/m/Y H:i'),
            'Trạng thái: '.$adjustment->progressLabel(),
            '',
            'Nội dung đề nghị (trước → sau):',
        ];
        foreach ($adjustment->items as $item) {
            $changes = [];
            foreach (['quantity' => ['Số lượng', ''], 'weight' => ['Khối lượng', ' kg'], 'price' => ['Đơn giá', '']] as $field => [$label, $unit]) {
                $original = (float) $item->{'original_'.$field};
                $adjusted = (float) $item->{'adjusted_'.$field};
                if (abs($original - $adjusted) > 0.001 || ! $item->order_item_id) {
                    $format = $field === 'price' ? $money : $number;
                    $changes[] = $label.': '.$format($original).$unit.' → '.$format($adjusted).$unit;
                }
            }
            if ($changes === []) {
                continue;
            }
            $name = $item->variant?->product?->name ?? $item->orderItem?->product?->name ?? 'Sản phẩm #'.$item->product_id;
            $size = $item->variant?->size;
            $lines[] = '- '.(! $item->order_item_id ? '[Thêm sản phẩm] ' : '').$name.($size ? ' (size '.$size.')' : '').': '.implode('; ', $changes);
            if ($item->note) {
                $lines[] = '  Ghi chú: '.$item->note;
            }
        }
        $labels = ['recipient_name' => 'Người nhận', 'recipient_phone' => 'Số điện thoại', 'delivery_time' => 'Giờ giao hàng'];
        foreach ((array) $adjustment->order_changes as $field => $change) {
            $lines[] = '- '.($labels[$field] ?? $field).': '.($change['original'] ?? '—').' → '.($change['adjusted'] ?? '—');
        }
        $feeLabels = ['vat' => 'Phí VAT', 'shipping' => 'Phí Ship', 'discount' => 'Chiết khấu đơn', 'foam_box' => 'Phí thùng xốp'];
        foreach ((array) $adjustment->fee_changes as $code => $change) {
            $original = (array) ($change['original'] ?? []);
            $adjusted = (array) ($change['adjusted'] ?? []);
            if ((bool) ($original['enabled'] ?? false) === (bool) ($adjusted['enabled'] ?? false)
                && abs((float) ($original['value'] ?? 0) - (float) ($adjusted['value'] ?? 0)) <= 0.001) {
                continue;
            }
            $formatFee = static fn (array $state): string => ! ($state['enabled'] ?? false) ? 'Không áp dụng'
                : (($change['calculation_type'] ?? 'fixed') === 'percent' ? $number($state['value'] ?? 0).'%' : $money($state['value'] ?? 0));
            $lines[] = '- '.($change['name'] ?? $feeLabels[$code] ?? $code).': '.$formatFee($original).' → '.$formatFee($adjusted);
        }
        if ($adjustment->adjustment_note) {
            $lines[] = 'Lý do / ghi chú: '.$adjustment->adjustment_note;
        }
        if ($adjustment->reject_reason) {
            $lines[] = 'Lý do từ chối: '.$adjustment->reject_reason;
        }
        $lines[] = 'Xem tiến trình: '.$url;

        return implode("\n", $lines);
    }
}
