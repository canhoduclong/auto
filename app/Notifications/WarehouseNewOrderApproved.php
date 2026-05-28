<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WarehouseNewOrderApproved extends Notification
{
    use Queueable;

    public function __construct(private readonly Order $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'warehouse_new_order_approved',
            'order_id' => $this->order->id,
            'order_code' => $this->order->code,
            'title' => 'Phát sinh đơn hàng mới cần xử lý.',
            'message' => 'Vui lòng kiểm tra thông tin đơn hàng và tiến hành đóng hàng.',
            'url' => route('warehouse.orders', ['date' => optional($this->order->created_at)->toDateString()]),
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
