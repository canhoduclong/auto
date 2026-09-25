<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WarehouseOrderAdjustmentConfirmed extends Notification
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
            'type' => 'warehouse_order_adjustment_confirmed',
            'order_id' => $this->order->id,
            'order_code' => $this->order->code,
            'title' => 'Sale da xac nhan dieu chinh don',
            'message' => 'Don ' . $this->order->code . ' da duoc xac nhan, kho co the dong hang.',
            'url' => route('warehouse.orders', ['date' => optional($this->order->created_at)->toDateString()]),
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
