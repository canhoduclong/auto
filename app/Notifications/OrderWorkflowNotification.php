<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderWorkflowNotification extends Notification
{
    use Queueable;

    public const SUBMITTED = 'submitted';
    public const APPROVED = 'approved';

    public function __construct(
        private readonly Order $order,
        private readonly string $event,
        private readonly ?int $actorId = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->order->loadMissing(['customer:id,name', 'user:id,name']);

        $orderCode = $this->order->code ?: ('#' . $this->order->id);
        $customerName = $this->order->customer?->name ?: 'Khách hàng';
        $saleName = $this->order->user?->name ?: 'Sale';
        $isManager = method_exists($notifiable, 'hasRole')
            && $notifiable->hasRole(['manager', 'manager_sale', 'director']);
        $isLeader = method_exists($notifiable, 'hasRole')
            && $notifiable->hasRole(['leader', 'leader_sale', 'sale_manager']);

        if ($this->event === self::APPROVED) {
            $title = 'Đơn ' . $orderCode . ' đã được Manager duyệt';
            $message = $customerName . ' • Sale: ' . $saleName;
        } else {
            $title = 'Đơn ' . $orderCode . ' đã được Sale gửi duyệt';
            $message = $customerName . ' • Sale: ' . $saleName;
        }

        $url = match (true) {
            $isManager => route('pages.all_team_orders', ['highlight' => $this->order->id]),
            $isLeader => route('pages.my_team_orders', ['highlight' => $this->order->id]),
            default => route('pages.my_orders', ['highlight' => $this->order->id]),
        };

        return [
            'type' => 'order_workflow_' . $this->event,
            'event' => $this->event,
            'order_id' => $this->order->id,
            'order_code' => $orderCode,
            'customer_name' => $customerName,
            'sale_user_id' => $this->order->user_id,
            'sale_name' => $saleName,
            'actor_id' => $this->actorId,
            'title' => $title,
            'message' => $message,
            'url' => $url,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
