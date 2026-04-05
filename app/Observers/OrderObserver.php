<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\AdminActivityService;

class OrderObserver
{
    public function created(Order $order): void
    {
        AdminActivityService::record(
            'order',
            'created',
            $order,
            'Tao moi don hang',
            'Don hang "' . ($order->code ?: ('#' . $order->id)) . '" vua duoc tao.',
            ['order_id' => $order->id, 'code' => $order->code, 'status' => $order->status],
            route('orders.show', $order)
        );
    }

    public function updated(Order $order): void
    {
        $action = $order->wasChanged('status') ? 'status_changed' : 'updated';

        AdminActivityService::record(
            'order',
            $action,
            $order,
            'Cap nhat don hang',
            'Don hang "' . ($order->code ?: ('#' . $order->id)) . '" da duoc cap nhat.',
            ['order_id' => $order->id, 'changes' => $order->getChanges()],
            route('orders.show', $order)
        );
    }

    public function deleted(Order $order): void
    {
        AdminActivityService::record(
            'order',
            'deleted',
            $order,
            'Xoa don hang',
            'Don hang "' . ($order->code ?: ('#' . $order->id)) . '" da bi xoa.',
            ['order_id' => $order->id, 'code' => $order->code],
            route('orders.index')
        );
    }
}
