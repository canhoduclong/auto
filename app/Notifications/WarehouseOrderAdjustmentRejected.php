<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WarehouseOrderAdjustmentRejected extends Notification
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
        $reason = trim((string) ($this->order->warehouse_adjustment_rejected_reason ?? ''));

        return [
            'type' => 'warehouse_order_adjustment_rejected',
            'order_id' => $this->order->id,
            'order_code' => $this->order->code,
            'title' => 'Sale da tu choi dieu chinh don',
            'message' => 'Don ' . $this->order->code . ' da bi tu choi dieu chinh.' . ($reason !== '' ? ' Ly do: ' . $reason : ''),
            'url' => route('warehouse.orders', ['date' => optional($this->order->created_at)->toDateString()]),
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
