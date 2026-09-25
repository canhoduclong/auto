<?php

namespace App\Exports;

use App\Models\ShipperDispatchHistory;
use Maatwebsite\Excel\Concerns\{FromArray, WithHeadings, WithCustomValueBinder, ShouldAutoSize};
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class DispatchHistoryExport extends StringValueBinder implements FromArray, WithHeadings, WithCustomValueBinder, ShouldAutoSize
{
    public function __construct(private ShipperDispatchHistory $history) {}

    public function headings(): array
    {
        return ['Ngày', 'Lần gửi', 'Shipper', 'Chuyến', 'STT', 'Mã đơn/ID', 'Giờ giao', 'Khách hàng', 'Sản phẩm', 'Số lượng', 'Điểm đi', 'Điểm đến', 'Phí ship', 'Ghi chú'];
    }

    public function array(): array
    {
        $rows = [];
        $total = 0;
        foreach ($this->history->route_plan ?? [] as $shipper) {
            foreach ($shipper['routes'] ?? [] as $route) {
                foreach ($route['orders'] ?? [] as $order) {
                    $fee = (float) ($order['final_fee'] ?? 0);
                    $total += $fee;
                    $rows[] = [$this->history->schedule_date->format('d/m/Y'), $this->history->version,
                        $shipper['shipper_name'] ?? '', $route['name'] ?? '', $order['sequence'] ?? '',
                        $order['order_code'] ?? $order['code'] ?? $order['order_id'] ?? '',
                        $order['delivery_time'] ?? '', $order['customer_name'] ?? '', $order['product_summary'] ?? '',
                        $order['quantity'] ?? 0, $order['origin'] ?? '', $order['destination'] ?? '', $fee, $order['note'] ?? ''];
                }
            }
        }
        $rows[] = ['Tổng phí ship', '', '', '', '', '', '', '', '', '', '', '', $total, ''];
        return $rows;
    }
}
