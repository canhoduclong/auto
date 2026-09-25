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
        $this->order->loadMissing([
            'customer:id,name',
            'user:id,name',
            'items.product:id,name',
            'items.variant:id,name,sku,product_id',
        ]);

        $sequence = $this->order->daily_sequence ?: $this->order->id;
        $customerName = $this->order->customer?->name ?: 'Khách hàng';
        $saleName = $this->order->user?->name ?: 'Chưa xác định';
        $createdTime = optional($this->order->created_at)->format('H:i d/m/Y') ?: '-';
        $products = $this->order->items->map(function ($item) {
            $quantity = (float) ($item->quantity ?? 0);
            $price = (float) ($item->price ?? 0);
            $lineTotal = (float) ($item->total ?? ($quantity * $price));

            return [
                'name' => (string) ($item->variant?->name ?? $item->product?->name ?? 'Sản phẩm'),
                'sku' => (string) ($item->variant?->sku ?? ''),
                'quantity' => $quantity,
                'price' => $price,
                'line_total' => $lineTotal,
            ];
        })->values()->all();

        $targetRoute = method_exists($notifiable, 'hasRole') && $notifiable->hasRole('package')
            ? 'package.orders'
            : 'warehouse.orders';

        return [
            'type' => 'warehouse_new_order_approved',
            'order_id' => $this->order->id,
            'order_code' => $this->order->code,
            'daily_sequence' => $this->order->daily_sequence,
            'customer_name' => $customerName,
            'sale_name' => $saleName,
            'order_created_at' => optional($this->order->created_at)->toIso8601String(),
            'products' => $products,
            'total' => (float) ($this->order->total ?? 0),
            'note' => (string) ($this->order->note ?? ''),
            'title' => 'Đơn mới: #' . $sequence . ' - ' . $customerName,
            'message' => 'Sale: ' . $saleName . ' • Giờ tạo: ' . $createdTime,
            'priority' => 'warning',
            'route_key' => 'orders',
            'url' => route($targetRoute, ['date' => optional($this->order->created_at)->toDateString(), 'highlight' => $this->order->id]),
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
