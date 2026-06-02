<?php

namespace App\Notifications;

use App\Models\AccountingReconciliation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AccountingOrderRevenueConfirmed extends Notification
{
    use Queueable;

    public function __construct(private readonly AccountingReconciliation $reconciliation)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $order = $this->reconciliation->order;
        $orderCode = $order?->code ?: ('#' . $this->reconciliation->order_id);

        return [
            'type' => 'accounting_order_revenue_confirmed',
            'order_id' => $this->reconciliation->order_id,
            'order_code' => $orderCode,
            'recognized_revenue' => (float) $this->reconciliation->recognized_revenue,
            'confirmed_at' => optional($this->reconciliation->confirmed_at)->toDateTimeString(),
            'title' => 'Chuc mung! Don hang da ghi nhan doanh thu',
            'message' => 'Don ' . $orderCode . ' da duoc ke toan xac nhan va ghi nhan doanh thu.',
            'url' => route('pages.my_dashboard'),
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
