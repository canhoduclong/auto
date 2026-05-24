<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WarehouseOrderAdjustmentRequested extends Notification
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
            'type' => 'warehouse_order_adjustment_requested',
            'order_id' => $this->order->id,
            'order_code' => $this->order->code,
            'title' => 'Kho da gui yeu cau dieu chinh don',
            'message' => 'Don ' . $this->order->code . ' dang cho sale xac nhan thay doi.',
            'url' => route('pages.my_dashboard'),
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
