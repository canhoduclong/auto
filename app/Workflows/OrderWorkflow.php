<?php

namespace App\Workflows;

use App\Enums\OrderStatus;

class OrderWorkflow
{
    protected array $transitions = [
        // Đơn mới tạo (Sale) -> chờ Leader duyệt
        OrderStatus::Draft->value => [OrderStatus::Pending->value],

        // Leader duyệt đơn -> chuyển sang trạng thái LeaderConfirmed
        OrderStatus::Pending->value => [OrderStatus::LeaderConfirmed->value],

        // Sau khi Leader duyệt -> chuyển sang Kế toán duyệt
        OrderStatus::LeaderConfirmed->value => [OrderStatus::AccountingPlanned->value],

        // Sau khi Kế toán duyệt -> chuyển sang Giám đốc duyệt
        OrderStatus::AccountingPlanned->value => [OrderStatus::ManagerConfirmed->value],

        // Sau khi Giám đốc duyệt -> chuyển sang xác nhận bởi Kho hoặc Nhà máy
        OrderStatus::ManagerConfirmed->value => [
            OrderStatus::WarehouseConfirmed->value, // Kho xác nhận
            OrderStatus::FactoryConfirmed->value    // Nhà máy xác nhận
        ],

        // Sau khi Kho xác nhận -> sang trạng thái Shipping (đang giao)
        OrderStatus::WarehouseConfirmed->value => [OrderStatus::Shipping->value],

        // Sau khi Nhà máy xác nhận -> sang trạng thái Shipping (đang giao)
        OrderStatus::FactoryConfirmed->value => [OrderStatus::Shipping->value],

        // Khi đang giao (Shipping) -> có thể hoàn tất (Delivered) hoặc bị trả lại (Returned)
        OrderStatus::Shipping->value => [
            OrderStatus::Delivered->value, // Giao thành công
            OrderStatus::Returned->value   // Bị trả hàng
        ],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, $this->transitions[$from] ?? []);
    }
}
