<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ShipperDeliveryScheduleUpdated extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $scheduleDate,
        private readonly int $orderCount,
        private readonly bool $routeChanged,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'shipper_delivery_schedule_updated',
            'title' => $this->routeChanged ? 'Lộ trình giao hàng đã thay đổi' : 'Bạn có lộ trình giao hàng mới',
            'message' => 'Lộ trình ngày ' . $this->scheduleDate . ' gồm ' . $this->orderCount . ' đơn. Vui lòng kiểm tra và xác nhận.',
            'priority' => 'warning',
            'route_key' => 'delivery-schedules',
            'url' => route('shipper.delivery-schedules', ['date' => $this->scheduleDate]),
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
